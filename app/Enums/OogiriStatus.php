<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 大喜利の状態（FEATURES.md 0-4、SPEC A-03）
 *
 * open → awaiting_selection → decided | no_result
 * hidden / deleted は管理操作・通報による別系統の状態
 */
enum OogiriStatus: string
{
    case Open              = 'open';               // 募集中
    case AwaitingSelection = 'awaiting_selection'; // 結果選択待ち
    case Decided           = 'decided';            // 結果確定
    case NoResult          = 'no_result';          // 結果未選出（72時間経過）
    case Hidden            = 'hidden';             // 一時非表示
    case Deleted           = 'deleted';            // 削除済み

    public function label(): string
    {
        return match ($this) {
            self::Open              => '募集中',
            self::AwaitingSelection => '結果選択待ち',
            self::Decided           => '結果確定',
            self::NoResult          => '結果未選出',
            self::Hidden            => '一時非表示',
            self::Deleted           => '削除済み',
        };
    }

    /**
     * 募集URL（/o/{slug}）で表示する画面ID
     */
    public function screen(): string
    {
        return match ($this) {
            self::Open                  => 'U-04',
            self::AwaitingSelection     => 'U-11',
            self::Decided               => 'U-10',
            self::NoResult              => 'U-12',
            self::Hidden, self::Deleted => 'U-13',
        };
    }

    /**
     * 回答を受け付けるか（締切判定は必ずサーバー側で行う：S-01）
     */
    public function acceptsAnswers(): bool
    {
        return $this === self::Open;
    }
}
