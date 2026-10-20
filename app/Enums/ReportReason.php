<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 通報理由（選択式。partials/modal_report と同じ値）
 */
enum ReportReason: string
{
    case Abuse          = 'abuse';
    case Personal       = 'personal';
    case Discrimination = 'discrimination';
    case Sexual         = 'sexual';
    case Rights         = 'rights';
    case Spam           = 'spam';
    case Other          = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Abuse          => '誹謗中傷・いじめ',
            self::Personal       => '個人情報',
            self::Discrimination => '差別・脅迫',
            self::Sexual         => '性的',
            self::Rights         => '権利侵害',
            self::Spam           => 'スパム・宣伝',
            self::Other          => 'その他',
        };
    }
}
