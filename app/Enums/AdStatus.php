<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 広告の状態（SPEC A-07）
 *
 * DB には draft / published / stopped だけを保存し（Ad::STATUS_*）、published は掲載期間で scheduled / live / ended に分けて表示する。
 */
enum AdStatus: string
{
    case Draft     = 'draft';     // 下書き（未公開）
    case Scheduled = 'scheduled'; // 公開済み・掲載開始前
    case Live      = 'live';      // 掲載中
    case Ended     = 'ended';     // 掲載期間が終了
    case Stopped   = 'stopped';   // 管理者が停止

    public function label(): string
    {
        return match ($this) {
            self::Draft     => '下書き',
            self::Scheduled => '予約',
            self::Live      => '掲載中',
            self::Ended     => '終了',
            self::Stopped   => '停止',
        };
    }
}
