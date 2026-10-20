<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 通報・OGP再生成などの対象種別（お題＝大喜利 ／ 回答）
 */
enum ReportTarget: string
{
    case Oogiri = 'oogiri';
    case Answer = 'answer';

    public function label(): string
    {
        return match ($this) {
            self::Oogiri => 'お題',
            self::Answer => '回答',
        };
    }
}
