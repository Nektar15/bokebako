<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Models\RestrictionModel;
use CodeIgniter\I18n\Time;

/**
 * レート制限と投稿元の制限（S-03、FEATURES 0-3）
 *
 * 判定は CodeIgniter の Throttler。キーは「操作の種類＋ブラウザIDのハッシュ＋IPのハッシュ」（CODING_RULES 7章）。
 * 閾値（回数・時間窓）と制限方法は運用設定（A-10）から読む。
 */
final class RateLimitService
{
    public const ACTION_CREATE     = 'create';
    public const ACTION_ANSWER     = 'answer';
    public const ACTION_REPORT     = 'report';
    public const ACTION_PASSPHRASE = 'passphrase';
    public const ACTION_CONTACT    = 'contact';

    /**
     * 合言葉の試行回数の上限（10分あたり）。運用設定にはしない（CODING_RULES 5章：固定値はクラス定数）
     */
    public const PASSPHRASE_ATTEMPTS = 5;

    public const PASSPHRASE_WINDOW_SEC = 600;

    public function __construct(
        private readonly SettingService $settings,
        private readonly IdentityService $identity,
        private readonly RestrictionModel $restrictions,
    ) {
    }

    /**
     * 投稿してよいか。超えていれば RateLimited を投げ、超えた直後なら制限方法に応じて投稿停止を記録する
     *
     * @throws AppException RATE_LIMITED
     */
    public function assertAllowed(string $action): void
    {
        if ($this->isRestricted()) {
            throw new AppException(ErrorCode::RateLimited);
        }
        $count  = $action === self::ACTION_PASSPHRASE ? self::PASSPHRASE_ATTEMPTS : $this->settings->int('burst_count');
        $window = $action === self::ACTION_PASSPHRASE ? self::PASSPHRASE_WINDOW_SEC : $this->settings->int('burst_window_sec');

        if (service('throttler')->check($this->key($action), $count, $window)) {
            return;
        }
        if ($action !== self::ACTION_PASSPHRASE && $this->settings->string('limit_method') === 'ban') {
            $this->restrict($this->settings->int('ban_minutes') * 60, '連続投稿を検知（' . $action . '）');
        }

        throw new AppException(ErrorCode::RateLimited);
    }

    /**
     * 異常投稿とみなす回数を超えたか（判定だけ。S-03 の「異常投稿候補」）
     */
    public function isBursting(string $action): bool
    {
        $key   = $this->key($action) . '_watch';
        $count = (int) cache($key) + 1;
        cache()->save($key, $count, $this->settings->int('burst_window_sec'));

        return $count > $this->settings->int('burst_count');
    }

    /**
     * 今のブラウザ・IP が投稿停止中か（restrictions テーブル。期限切れは無視）
     */
    public function isRestricted(): bool
    {
        $browser = $this->identity->browserIdHash();
        $ip      = $this->identity->ipHash();
        $now     = Time::now()->toDateTimeString();

        $builder = $this->restrictions->builder()->where('until >', $now)->groupStart()->where('ip_hash', $ip);
        if ($browser !== '') {
            $builder->orWhere('browser_id_hash', $browser);
        }

        return $builder->groupEnd()->countAllResults() > 0;
    }

    /**
     * 今のブラウザ・IP を一定時間投稿停止にする（S-03 の制限方法「投稿停止」、A-05「投稿元を制限」）
     */
    public function restrict(int $seconds, string $reason, ?string $browserIdHash = null, ?string $ipHash = null): void
    {
        $this->restrictions->insert([
            'browser_id_hash' => $browserIdHash ?? ($this->identity->browserIdHash() ?: null),
            'ip_hash'         => $ipHash ?? $this->identity->ipHash(),
            'until'           => Time::now()->addSeconds($seconds)->toDateTimeString(),
            'reason'          => $reason,
            'created_at'      => Time::now()->toDateTimeString(),
        ]);
    }

    /**
     * 制限方法が CAPTCHA のとき、連続投稿の閾値を超えたら CAPTCHA を要求する（FEATURES 0-3）
     */
    public function needsCaptcha(string $action): bool
    {
        return $this->settings->string('limit_method') === 'captcha' && $this->isBursting($action);
    }

    private function key(string $action): string
    {
        // キャッシュキーに使える文字は英数と _- だけ（{}()/\@: は予約）
        return 'rl_' . $action . '_' . substr($this->identity->browserIdHash(), 0, 16) . '_' . substr($this->identity->ipHash(), 0, 16);
    }
}
