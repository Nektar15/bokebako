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
 * 判定は CodeIgniter の Throttler。キーは「操作の種類＋ブラウザIDのハッシュ＋IPのハッシュ」（CODING_RULES 7章）に加え、
 * 「操作の種類＋IPのハッシュ」でも数える（ADR 0006-3）。
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
     * 合言葉の試行回数の上限（PASSPHRASE_WINDOW_SEC あたり）：同じ IP から同じ大喜利へ。
     * 運用設定にはしない（CODING_RULES 5章：固定値はクラス定数）
     */
    public const PASSPHRASE_ATTEMPTS = 5;

    /**
     * 合言葉の試行回数の上限：同じ IP から（大喜利を問わず）
     */
    public const PASSPHRASE_IP_ATTEMPTS = 20;

    /**
     * 合言葉の試行回数の上限：同じ大喜利へ（IP を問わず。IP を変えながらの総当たり対策。ADR 0006-3【仮】）
     */
    public const PASSPHRASE_TARGET_ATTEMPTS = 30;

    public const PASSPHRASE_WINDOW_SEC = 600;

    /**
     * IP 単位の上限は「ブラウザ単位の上限 × この倍率」。携帯回線や学校などで IP を共有する人を巻き込みにくくする（ADR 0006-3【仮】）
     */
    public const IP_FACTOR = 5;

    public function __construct(
        private readonly SettingService $settings,
        private readonly IdentityService $identity,
        private readonly RestrictionModel $restrictions,
    ) {
    }

    /**
     * 投稿してよいか。超えていれば RateLimited を投げ、超えた直後なら制限方法に応じて投稿停止を記録する
     *
     * ブラウザIDは Cookie なので利用者が変えられる。ブラウザ単位に加えて IP 単位でも数え、
     * Cookie を変えても上限を回避できないようにする（レビュー指摘 3）
     *
     * @param string $scope 対象（合言葉ではスラッグ）。対象ごとにも数える操作だけ渡す
     *
     * @throws AppException RATE_LIMITED
     */
    public function assertAllowed(string $action, string $scope = ''): void
    {
        if ($this->isRestricted()) {
            throw new AppException(ErrorCode::RateLimited);
        }

        foreach ($this->limits($action, $scope) as $key => $count) {
            if (service('throttler')->check($key, $count, $this->window($action))) {
                continue;
            }
            if ($action !== self::ACTION_PASSPHRASE && $this->settings->string('limit_method') === 'ban') {
                $this->restrict($this->settings->int('ban_minutes') * 60, '連続投稿を検知（' . $action . '）');
            }

            throw new AppException(ErrorCode::RateLimited);
        }
    }

    /**
     * 異常投稿とみなす回数を超えたか（判定だけ。S-03 の「異常投稿候補」）。ブラウザ単位と IP 単位の両方で数える
     */
    public function isBursting(string $action): bool
    {
        $bursting = false;

        foreach ($this->limits($action) as $key => $count) {
            $key .= '_watch';
            $seen = (int) cache($key) + 1;
            cache()->save($key, $seen, $this->settings->int('burst_window_sec'));
            $bursting = $bursting || $seen > $count;
        }

        return $bursting;
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

    /**
     * 数える単位ごとのキーと上限回数
     *
     * @return array<string, int> Throttler のキー => 上限回数
     */
    private function limits(string $action, string $scope = ''): array
    {
        $browser = substr($this->identity->browserIdHash(), 0, 16);
        $ip      = substr($this->identity->ipHash(), 0, 16);

        if ($action === self::ACTION_PASSPHRASE) {
            // 合言葉は Cookie を持たないアクセス（総当たり）を想定し、ブラウザIDでは数えない
            $target = substr($this->identity->hash($scope), 0, 16);

            return [
                $this->key($action, 'i', $ip, 's', $target) => self::PASSPHRASE_ATTEMPTS,
                $this->key($action, 'i', $ip)               => self::PASSPHRASE_IP_ATTEMPTS,
                $this->key($action, 's', $target)           => self::PASSPHRASE_TARGET_ATTEMPTS,
            ];
        }
        $count = $this->settings->int('burst_count');

        return [
            $this->key($action, 'b', $browser, 'i', $ip) => $count,
            $this->key($action, 'i', $ip)                => $count * self::IP_FACTOR,
        ];
    }

    private function window(string $action): int
    {
        return $action === self::ACTION_PASSPHRASE ? self::PASSPHRASE_WINDOW_SEC : $this->settings->int('burst_window_sec');
    }

    private function key(string $action, string ...$parts): string
    {
        // キャッシュキーに使える文字は英数と _- だけ（{}()/\@: は予約）
        return 'rl_' . $action . '_' . implode('_', $parts);
    }
}
