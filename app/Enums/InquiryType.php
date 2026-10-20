<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * お問い合わせの種別（U-18 で選択、A-09 で絞り込み）
 */
enum InquiryType: string
{
    case General = 'general';
    case Delete  = 'delete';
    case Rights  = 'rights';
    case Ad      = 'ad';
    case Other   = 'other';

    public function label(): string
    {
        return match ($this) {
            self::General => '一般問い合わせ',
            self::Delete  => '投稿削除依頼',
            self::Rights  => '権利侵害',
            self::Ad      => '広告掲載について',
            self::Other   => 'その他',
        };
    }

    /**
     * 対象URLの入力が必須になる種別か
     */
    public function requiresUrl(): bool
    {
        return $this === self::Delete || $this === self::Rights;
    }

    /**
     * 値 => 表示名（セレクトやチップの描画用）
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * 対象URLが必須になる種別の値（U-18 の JS に渡す）
     *
     * @return list<string>
     */
    public static function urlRequiredValues(): array
    {
        $values = [];

        foreach (self::cases() as $case) {
            if ($case->requiresUrl()) {
                $values[] = $case->value;
            }
        }

        return $values;
    }
}
