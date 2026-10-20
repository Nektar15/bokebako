<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * AI補助判定の結果（S-05）。API エラー時は未判定
 */
enum AiVerdict: string
{
    case None   = 'none';   // 未判定
    case Ok     = 'ok';     // 問題なし
    case Review = 'review'; // 要確認
    case Hide   = 'hide';   // 非表示推奨

    public function label(): string
    {
        return match ($this) {
            self::None   => '未判定',
            self::Ok     => '問題なし',
            self::Review => '要確認',
            self::Hide   => '非表示推奨',
        };
    }
}
