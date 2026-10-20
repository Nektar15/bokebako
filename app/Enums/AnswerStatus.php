<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 回答の状態（FEATURES.md 0-4）
 */
enum AnswerStatus: string
{
    case Visible = 'visible';
    case Hidden  = 'hidden';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Visible => '公開',
            self::Hidden  => '一時非表示',
            self::Deleted => '削除済み',
        };
    }
}
