<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * NGワードに一致したときの処理（SPEC A-06、FEATURES 0-3）
 */
enum NgWordAction: string
{
    case Reject = 'reject'; // 投稿拒否：投稿時にエラー
    case Review = 'review'; // 要確認：投稿は通し、A-05 に表示

    public function label(): string
    {
        return match ($this) {
            self::Reject => '投稿拒否',
            self::Review => '要確認',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Reject => '投稿時にエラーにして、投稿させません。',
            self::Review => '投稿は通し、通報・モデレーションに表示します。',
        };
    }
}
