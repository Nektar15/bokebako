<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\AdminAccount;
use App\Enums\AdminRole;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Services\AdminAccountService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;

/**
 * A-01 管理者アカウント（追加・権限設定・パスワード。画面は未確定事項10 → ADR 0007-4）
 *
 * 一覧と管理は上位管理者だけ。一般管理者には自分のパスワード変更だけを出す。
 */
final class AccountController extends AdminController
{
    public function index(): string
    {
        $me      = service('currentAdmin')->get();
        $isSuper = $me->role === AdminRole::Super;
        $editId  = (int) $this->request->getGet('edit');
        $editing = $isSuper && $editId > 0 ? $this->findOrFail(static fn (): AdminAccount => service('adminAccount')->find($editId)) : null;
        $display = service('display');
        $rows    = [];

        foreach ($isSuper ? service('adminAccount')->list() : [] as $account) {
            $rows[] = [
                'account'   => $account,
                'role'      => $account->role()->label(),
                'lastLogin' => $account->last_login_at !== null ? $display->datetime($account->last_login_at) : '—',
                'locked'    => $account->locked_until !== null && $account->locked_until->isAfter(Time::now()),
                'isSelf'    => $account->id === $me->id,
            ];
        }

        return $this->render('admin/a01_accounts', [
            'screenId'    => 'A-01',
            'title'       => '管理者アカウント',
            'isSuper'     => $isSuper,
            'rows'        => $rows,
            'roles'       => AdminRole::cases(),
            'editing'     => $editing,
            'form'        => $this->formValues($editing),
            'passwordMin' => AdminAccountService::PASSWORD_MIN,
        ]);
    }

    public function create(): RedirectResponse
    {
        return $this->run(fn () => service('adminAccount')->create([
            'login_id' => (string) $this->request->getPost('login_id'),
            'name'     => (string) $this->request->getPost('name'),
            'role'     => (string) $this->request->getPost('role'),
            'password' => (string) $this->request->getPost('password'),
        ]), '管理者を追加しました', admin_url('accounts'));
    }

    public function update(int $id): RedirectResponse
    {
        return $this->run(fn () => service('adminAccount')->update($id, [
            'name' => (string) $this->request->getPost('name'),
            'role' => (string) $this->request->getPost('role'),
        ]), '名前・権限を保存しました', admin_url('accounts'));
    }

    public function resetPassword(int $id): RedirectResponse
    {
        return $this->run(fn () => service('adminAccount')->resetPassword($id, (string) $this->request->getPost('password')), 'パスワードを再設定しました', admin_url('accounts'));
    }

    public function delete(int $id): RedirectResponse
    {
        return $this->run(static fn () => service('adminAccount')->delete($id), '管理者を削除しました', admin_url('accounts'));
    }

    public function changeOwnPassword(): RedirectResponse
    {
        return $this->run(fn () => service('adminAccount')->changeOwnPassword(
            (string) $this->request->getPost('current_password'),
            (string) $this->request->getPost('password'),
        ), 'パスワードを変更しました', admin_url('accounts'));
    }

    /**
     * 操作して一覧へ戻る。入力エラーは入力値を保持して戻る（パスワード欄は View で初期値を出さない）
     */
    private function run(callable $action, string $notice, string $to): RedirectResponse
    {
        try {
            $this->orFail($action);
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        } catch (ForbiddenException $e) {
            return $this->backWithErrors(['_' => $e->getMessage()]);
        }

        return redirect()->to($to)->with('notice', $notice);
    }

    /**
     * フォームの初期値（入力エラーで戻ったときは入力値）
     *
     * @return array{login_id: string, name: string, role: string}
     */
    private function formValues(?AdminAccount $editing): array
    {
        return [
            'login_id' => (string) old('login_id', $editing?->login_id ?? ''),
            'name'     => (string) old('name', $editing?->name ?? ''),
            'role'     => (string) old('role', $editing?->role ?? AdminRole::General->value),
        ];
    }
}
