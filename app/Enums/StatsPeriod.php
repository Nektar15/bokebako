<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * ダッシュボード（A-02）の集計期間
 */
enum StatsPeriod: string
{
    case Today     = 'today';
    case Yesterday = 'yesterday';
    case Week      = '7d';
    case Month     = '30d';
    case Custom    = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today     => '今日',
            self::Yesterday => '昨日',
            self::Week      => '7日',
            self::Month     => '30日',
            self::Custom    => '期間指定',
        };
    }
}
