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
            self::Recruit => '中央にお題、背景にぼかした回答カード、ロゴ、右上の札',
            self::Answer  => '中央に回答、上にお題、背景にぼかした回答カード、ロゴ',
            self::Result  => '中央に1位、下の左に2位・右に3位、お題、判子、回答数、ロゴ',
        };
    }
}
