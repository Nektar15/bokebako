<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminAccount;
use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Models\AdminModel;
use CodeIgniter\I18n\Time;

/**
 * 管理者の認証（A-01）：ID・パスワードの照合、試行回数制限と一時ロック、セッションの開始・確認・終了
 *
 * 二段階認証（TOTP）は最終工程で、verifyPassword() と startSession() の間に挟む（ADR 0007-6）。
 * 数値はすべて【仮】（ADR 0007）。運用設定にはしない（CODING_RULES 5章：固定値はクラス定数）。
 */
final class AdminAuthService
{
    /**
     * 同じアカウントでこの回数続けて失敗したら一時ロックする（SPEC A-01「一定回数失敗で一時ロック」）
     */
    public const MAX_FAILURES = 5;

    public const LOCK_MINUTES = 15;

    /**
     * 同じ IP からの試行回数の上限（IP_WINDOW_SEC あたり。存在しない ID を含めた総当たり対策）
     */
    public const IP_ATTEMPTS = 20;

    public const IP_WINDOW_SEC = 600;

    /**
     * 操作がないままこの時間が過ぎたらログアウト扱い
     */
    public const IDLE_MINUTES = 120;

    /**
     * ログインからこの時間が過ぎたら、操作中でもログインし直す
     */
    public const MAX_HOURS = 12;

    // セッションのキー
    public const SESSION_ID          = 'bk_admin_id';
    public const SESSION_FINGERPRINT = 'bk_admin_fp';
    public const SESSION_LOGIN_AT    = 'bk_admin_login_at';
    public const SESSION_SEEN_AT     = 'bk_admin_seen_at';

    /**
     * 存在しない ID のときにも password_verify() を1回通すためのハッシュ（応答時間で ID の有無を推測させない）
     */
    private const DUMMY_HASH = '$2y$10$UMaEb8c3hs7dCV8Ptat12eTHy1VnNdc4RTuIAhm6PAAeJNf1Rb9.O';

    public function __construct(
        private readonly AdminModel $admins,
        private readonly IdentityService $identity,
    ) {
    }

    /**
     * ID（またはメールアドレス）とパスワードを照合する。成功したら失敗回数を戻し、アカウントを返す
     *
     * @throws AppException        IP の試行回数の上限、アカウントの一時ロック中
     * @throws ValidationException 入力なし、ID かパスワードが違う（どちらが違うかは言わない）
     */
    public function verifyPassword(string $loginId, string $password): AdminAccount
    {
        $loginId = trim($loginId);

        if ($loginId === '' || $password === '') {
            throw new ValidationException(['login' => 'ID とパスワードを入力してください']);
        }
        $ipKey = 'admin_login_' . substr($this->identity->ipHash(), 0, 16);

        if (! service('throttler')->check($ipKey, self::IP_ATTEMPTS, self::IP_WINDOW_SEC)) {
            throw new AppException(ErrorCode::RateLimited, '試行回数の上限に達しました。しばらくしてからお試しください');
        }
        $account = $this->admins->where('login_id', $loginId)->first();

        if ($account !== null && $this->isLocked($account)) {
            throw new AppException(ErrorCode::RateLimited, 'ログインを一時的に停止しています。しばらくしてからお試しください');
        }
        if (! password_verify($password, $account?->password_hash ?? self::DUMMY_HASH) || $account === null) {
            if ($account !== null) {
                $this->recordFailure($account);
            }

            throw new ValidationException(['login' => 'ID またはパスワードが違います']);
        }
        $this->admins->update($account->id, ['failed_count' => 0, 'locked_until' => null]);

        return $account;
    }

    /**
     * ログイン済みにする。セッション ID を作り直して、ログイン前の ID を使い回させない（セッション固定攻撃の対策）
     */
    public function startSession(AdminAccount $account): void
    {
        $now = Time::now()->getTimestamp();
        session()->regenerate(true);
        session()->set([
            self::SESSION_ID          => $account->id,
            self::SESSION_FINGERPRINT => self::fingerprint($account),
            self::SESSION_LOGIN_AT    => $now,
            self::SESSION_SEEN_AT     => $now,
        ]);
        $this->admins->update($account->id, ['last_login_at' => Time::now()->toDateTimeString()]);
        service('currentAdmin')->set($account->toAdminUser());
        service('adminLog')->record('admin', $account->id, 'ログイン');
    }

    /**
     * セッションのログイン中の管理者。いなければ（未ログイン・期限切れ・削除済み・パスワード変更済み）null にしてセッションを消す
     */
    public function current(): ?AdminAccount
    {
        $id = (int) session(self::SESSION_ID);

        if ($id === 0) {
            return null;
        }
        $now     = Time::now()->getTimestamp();
        $loginAt = (int) session(self::SESSION_LOGIN_AT);
        $seenAt  = (int) session(self::SESSION_SEEN_AT);
        $account = $this->admins->find($id);

        $valid = $account !== null
            && hash_equals((string) session(self::SESSION_FINGERPRINT), self::fingerprint($account))
            && $now - $seenAt <= self::IDLE_MINUTES * 60
            && $now - $loginAt <= self::MAX_HOURS * 3600;

        if (! $valid) {
            $this->clearSession();

            return null;
        }
        session()->set(self::SESSION_SEEN_AT, $now);

        return $account;
    }

    /**
     * ログアウト（A-01）
     */
    public function logout(): void
    {
        if (service('currentAdmin')->has()) {
            service('adminLog')->record('admin', service('currentAdmin')->get()->id, 'ログアウト');
        }
        $this->clearSession();
    }

    /**
     * 管理画面の IP 制限（SPEC 3.4「IPアドレス制限（任意）」）。許可リストが空なら制限しない
     *
     * @param string $allowedIps カンマ区切り（.env の ADMIN_ALLOWED_IPS）
     */
    public static function allowsIp(string $ip, string $allowedIps): bool
    {
        $list = array_values(array_filter(array_map('trim', explode(',', $allowedIps)), static fn (string $v) => $v !== ''));

        return $list === [] || in_array($ip, $list, true);
    }

    /**
     * パスワードハッシュから作る印。パスワードを変えたら、ほかの端末のセッションを無効にする
     */
    public static function fingerprint(AdminAccount $account): string
    {
        return substr(hash('sha256', $account->password_hash), 0, 32);
    }

    private function isLocked(AdminAccount $account): bool
    {
        return $account->locked_until !== null && Time::now()->isBefore($account->locked_until);
    }

    /**
     * 失敗を数える（読み出してから足さない：CODING_RULES 6章）。上限に達したら一時ロックし、数え直す
     */
    private function recordFailure(AdminAccount $account): void
    {
        $this->admins->increment($account->id, 'failed_count');
        $failed = (int) $this->admins->find($account->id)?->failed_count;

        if ($failed >= self::MAX_FAILURES) {
            $this->admins->update($account->id, ['failed_count' => 0, 'locked_until' => Time::now()->addMinutes(self::LOCK_MINUTES)->toDateTimeString()]);
            service('adminLog')->record('admin', $account->id, 'ログインの失敗が続いたため一時ロック', AdminLogService::ACTOR_AUTO);
        }
    }

    private function clearSession(): void
    {
        session()->remove([self::SESSION_ID, self::SESSION_FINGERPRINT, self::SESSION_LOGIN_AT, self::SESSION_SEEN_AT]);
        session()->regenerate(true);
        service('currentAdmin')->clear();
    }
}
