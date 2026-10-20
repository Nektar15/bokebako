<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * OGP 画像の種類（SPEC A-08・S-07）
 */
enum OgpKind: string
{
    case Recruit = 'recruit'; // 募集用：開催時に生成
    case Answer  = 'answer';  // 回答シェア用：初回アクセス時に生成
    case Result  = 'result';  // 結果用：結果確定時に生成

    public function label(): string
    {
        return match ($this) {
            self::Recruit => '募集用',
            self::Answer  => '回答シェア用',
            self::Result  => '結果用',
        };
    }

    /**
     * 生成されるタイミングの説明（A-08 のカード見出し）
     */
    public function timing(): string
    {
        return match ($this) {
            self::Recruit => '開催時に生成',
            self::Answer  => '初回アクセス時に生成',
            self::Result  => '結果確定時に生成',
        };
    }

    /**
     * 画像に入る要素の説明
     */
    public function elements(): string
    {
        return match ($this) {
            self::Recruit => 'ロゴ、お題、「24時間限定」',
            self::Answer  => 'お題、回答、ロゴ',
            self::Result  => 'お題、1位の回答、回答数、ロゴ',
        };
    }
}
