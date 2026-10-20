<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * お問い合わせの対応状況（SPEC A-09）
 */
enum InquiryStatus: string
{
    case Todo  = 'todo';
    case Doing = 'doing';
    case Done  = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo  => '未対応',
            self::Doing => '対応中',
            self::Done  => '対応済み',
        };
    }
}
