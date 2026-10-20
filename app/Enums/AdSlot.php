<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 広告の掲載場所（SPEC A-07：初期広告枠は上部・下部の2つ）
 */
enum AdSlot: string
{
    case Top    = 'top';
    case Bottom = 'bottom';

    public function label(): string
    {
        return match ($this) {
            self::Top    => '上部',
            self::Bottom => '下部',
        };
    }
}
