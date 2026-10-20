<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * NGワードの一致方式（SPEC A-06）
 */
enum NgWordMatchType: string
{
    case Exact   = 'exact';
    case Partial = 'partial';
    case Regex   = 'regex';

    public function label(): string
    {
        return match ($this) {
            self::Exact   => '完全一致',
            self::Partial => '部分一致',
            self::Regex   => '正規表現',
        };
    }

    /**
     * 追加フォームの説明文
     */
    public function help(): string
    {
        return match ($this) {
            self::Exact   => '投稿全体がこのワードと同じときだけ一致します。',
            self::Partial => '投稿の中にこのワードが含まれていれば一致します。',
            self::Regex   => '前後の / は不要です。保存時に、正しい正規表現かどうかを確認します。',
        };
    }
}
