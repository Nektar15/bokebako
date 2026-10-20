<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AdSlot;
use App\Enums\AdStatus;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 広告（ads テーブルの1行。SPEC 3.3）
 *
 * @property int    $clicks
 * @property Time   $ends_at
 * @property int    $id
 * @property string $image       画像のパス（public/ からの相対）
 * @property int    $impressions
 * @property string $name        広告名
 * @property string $slot        AdSlot の値
 * @property string $sponsor     スポンサー名
 * @property Time   $starts_at
 * @property string $status      保存される表示状態：draft（下書き）/ published（公開）/ stopped（停止）。掲載中・予約・終了は期間から判定する
 * @property string $url         リンク先
 */
final class Ad extends Entity
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_STOPPED   = 'stopped';

    protected $dates = ['starts_at', 'ends_at', 'created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'impressions' => 'int', 'clicks' => 'int'];

    /**
     * 表示用の状態（公開中の広告は掲載期間で 予約 / 掲載中 / 終了 に分かれる）
     */
    public function status(?Time $now = null): AdStatus
    {
        $now ??= Time::now();

        return match ($this->attributes['status']) {
            self::STATUS_DRAFT   => AdStatus::Draft,
            self::STATUS_STOPPED => AdStatus::Stopped,
            default              => match (true) {
                $now->isBefore($this->starts_at) => AdStatus::Scheduled,
                $now->isAfter($this->ends_at)    => AdStatus::Ended,
                default                          => AdStatus::Live,
            },
        };
    }

    public function slot(): AdSlot
    {
        return AdSlot::from($this->attributes['slot']);
    }
}
