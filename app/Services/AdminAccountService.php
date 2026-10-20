<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminAccount;
use App\Enums\AdminRole;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\AdminModel;

/**
 * 管理者アカウントの追加・権限設定・パスワード（A-01。画面は未確定事項10 → ADR 0007-4）
 *
 * 自分のパスワード変更はどの管理者もできる。それ以外は上位管理者だけ（Service でも確認する：CODING_RULES 7章）。
 */
final class AdminAccountService
{
    /**
     * パスワードの最小文字数【仮】。最大は bcrypt が扱える 72 バイト
     */
    public const PASSWORD_MIN = 12;

    public const PASSWORD_MAX_BYTES = 72;

    /**
     * 一覧の上限（管理者が数十人を超えることは想定しない）
     */
    public const LIST_LIMIT = 100;

    /**
     * 操作履歴の「誰が」（コマンドから追加したとき）
     */
    public const ACTOR_COMMAND = 'コマンド';

    public function __construct(
        private readonly AdminModel $admins,
    ) {
    }

    /**
     * @return list<AdminAccount>
     */
    public function list(): array
    {
        return $this->admins->orderBy('id', 'ASC')->findAll(self::LIST_LIMIT);
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): AdminAccount
    {
        $account = $this->admins->find($id);

        if ($account === null) {
            throw new NotFoundException();
        }

        return $account;
    }

    /**
     * 追加する（上位管理者）
     *
     * @param array{login_id?: string, name?: string, role?: string, password?: string} $input
     *
     * @throws ForbiddenException
     * @throws ValidationException
     */
    public function create(array $input): AdminAccount
    {
        $this->assertSuper();

        return $this->insert($input, null);
    }

    /**
     * コマンド（php spark admin:create）から追加する。最初の上位管理者を作るため、ログイン中の管理者を要らない
     *
     * @param array{login_id?: string, name?: string, role?: string, password?: string} $input
     *
     * @throws ValidationException
     */
    public function createFromCommand(array $input): AdminAccount
    {
        return $this->insert($input, self::ACTOR_COMMAND);
    }

    /**
     * 名前と権限を変える（上位管理者）。自分の権限は変えられない。上位管理者を0人にはできない
     *
     * @param array{name?: string, role?: string} $input
     *
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function update(int $id, array $input): void
    {
        $this->assertSuper();
        $account = $this->find($id);
        $errors  = $this->validateProfile($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        $role = AdminRole::from((string) $input['role']);

        if ($role !== $account->role() && $this->isSelf($account)) {
            throw new ValidationException(['role' => '自分の権限は変更できません（ほかの上位管理者に依頼してください）']);
        }
        if ($role !== AdminRole::Super && $account->role() === AdminRole::Super && $this->superCount() <= 1) {
            throw new ValidationException(['role' => '上位管理者が1人もいなくなるため変更できません']);
        }
        $this->admins->update($id, ['name' => trim((string) $input['name']), 'role' => $role->value]);
        service('adminLog')->record('admin', $id, '名前・権限を変更（' . $role->label() . '）');
    }

    /**
     * ほかの管理者のパスワードを再設定する（上位管理者）。一時ロックも解く
     *
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function resetPassword(int $id, string $password): void
    {
        $this->assertSuper();
        $account = $this->find($id);
        $errors  = $this->validatePassword($password, $account->login_id);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        $this->admins->update($id, ['password_hash' => password_hash($password, PASSWORD_BCRYPT), 'failed_count' => 0, 'locked_until' => null]);
        service('adminLog')->record('admin', $id, 'パスワードを再設定');
    }

    /**
     * 自分のパスワードを変える（どの管理者も）。今のパスワードの確認が要る
     *
     * @throws ValidationException
     */
    public function changeOwnPassword(string $current, string $new): void
    {
        $account = $this->find(service('currentAdmin')->get()->id);

        if (! password_verify($current, $account->password_hash)) {
            throw new ValidationException(['current_password' => '今のパスワードが違います']);
        }
        $errors = $this->validatePassword($new, $account->login_id);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        $this->admins->update($account->id, ['password_hash' => password_hash($new, PASSWORD_BCRYPT)]);
        service('adminLog')->record('admin', $account->id, '自分のパスワードを変更');
        // パスワードを変えるとセッションの印が合わなくなるので、この端末はログインし直しにせず印を入れ直す
        service('adminAuth')->startSession($this->find($account->id));
    }

    /**
     * 削除する（上位管理者）。自分と、最後の上位管理者は削除できない。操作履歴は名前で残っているので物理削除する
     *
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function delete(int $id): void
    {
        $this->assertSuper();
        $account = $this->find($id);

        if ($this->isSelf($account)) {
            throw new ValidationException(['_' => '自分のアカウントは削除できません']);
        }
        if ($account->role() === AdminRole::Super && $this->superCount() <= 1) {
            throw new ValidationException(['_' => '上位管理者が1人もいなくなるため削除できません']);
        }
        $this->admins->delete($id);
        service('adminLog')->record('admin', $id, '管理者を削除（' . $account->login_id . '）');
    }

    /**
     * @param array{login_id?: string, name?: string, role?: string, password?: string} $input
     *
     * @throws ValidationException
     */
    private function insert(array $input, ?string $actor): AdminAccount
    {
        $loginId = trim((string) ($input['login_id'] ?? ''));
        $errors  = $this->validateProfile($input) + $this->validateLoginId($loginId) + $this->validatePassword((string) ($input['password'] ?? ''), $loginId);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        $role = AdminRole::from((string) $input['role']);
        $id   = (int) $this->admins->insert([
            'login_id'      => $loginId,
            'name'          => trim((string) $input['name']),
            'role'          => $role->value,
            'password_hash' => password_hash((string) $input['password'], PASSWORD_BCRYPT),
            'failed_count'  => 0,
        ]);
        service('adminLog')->record('admin', $id, '管理者を追加（' . $loginId . '、' . $role->label() . '）', $actor);

        return $this->find($id);
    }

    /**
     * パスワードの条件（追加・再設定・変更で共通）
     *
     * @return array<string, string>
     */
    public function validatePassword(string $password, string $loginId): array
    {
        if (mb_strlen($password) < self::PASSWORD_MIN) {
            return ['password' => 'パスワードは' . self::PASSWORD_MIN . '文字以上にしてください'];
        }
        if (strlen($password) > self::PASSWORD_MAX_BYTES) {
            return ['password' => 'パスワードが長すぎます（半角' . self::PASSWORD_MAX_BYTES . '文字まで）'];
        }
        if ($loginId !== '' && str_contains(mb_strtolower($password), mb_strtolower($loginId))) {
            return ['password' => 'パスワードに ID を含めないでください'];
        }

        return [];
    }

    /**
     * @param array{name?: string, role?: string} $input
     *
     * @return array<string, string>
     */
    private function validateProfile(array $input): array
    {
        $errors = [];
        $name   = trim((string) ($input['name'] ?? ''));

        if ($name === '' || mb_strlen($name) > 64) {
            $errors['name'] = '表示名は1〜64文字で入力してください';
        }
        if (AdminRole::tryFrom((string) ($input['role'] ?? '')) === null) {
            $errors['role'] = '権限を選んでください';
        }

        return $errors;
    }

    /**
     * ID は英数字と . _ @ + -（メールアドレスも使える）。3〜64文字、重複なし
     *
     * @return array<string, string>
     */
    private function validateLoginId(string $loginId): array
    {
        if (preg_match('/\A[A-Za-z0-9._@+\-]{3,64}\z/', $loginId) !== 1) {
            return ['login_id' => 'ID は半角英数字と . _ @ + - で3〜64文字にしてください（メールアドレスも使えます）'];
        }
        if ($this->admins->where('login_id', $loginId)->countAllResults() > 0) {
            return ['login_id' => 'この ID はすでに使われています'];
        }

        return [];
    }

    private function superCount(): int
    {
        return $this->admins->where('role', AdminRole::Super->value)->countAllResults();
    }

    private function isSelf(AdminAccount $account): bool
    {
        return service('currentAdmin')->get()->id === $account->id;
    }

    /**
     * @throws ForbiddenException
     */
    private function assertSuper(): void
    {
        if (service('currentAdmin')->get()->role !== AdminRole::Super) {
            throw new ForbiddenException('この操作は上位管理者だけができます');
        }
    }
}
