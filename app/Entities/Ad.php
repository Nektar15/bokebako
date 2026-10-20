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
 * @property string $status      AdStatus の値
 * @property string $url         リンク先
 */
final class Ad extends Entity
{
    protected $dates = ['starts_at', 'ends_at', 'created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'impressions' => 'int', 'clicks' => 'int'];

    public function status(): AdStatus
    {
        return AdStatus::from($this->attributes['status']);
    }

    public function slot(): AdSlot
    {
        return AdSlot::from($this->attributes['slot']);
    }
}
