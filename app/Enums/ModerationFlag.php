<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 通報・モデレーション（A-05）で優先表示する理由。並び順はこの定義順（SPEC A-05「優先表示」）
 */
enum ModerationFlag: string
{
    case Surge    = 'surge';     // 通報急増
    case Reported = 'reported';  // 一定件数以上の通報
    case NgWord   = 'ng_word';   // NGワード「要確認」を検知
    case Abnormal = 'abnormal';  // 異常投稿判定（S-03）
    case AiReview = 'ai_review'; // AI判定「要確認」
    case AiHide   = 'ai_hide';   // AI判定「非表示推奨」

    public function label(): string
    {
        return match ($this) {
            self::Surge    => '通報急増',
            self::Reported => '通報',
            self::NgWord   => 'NG検知',
            self::Abnormal => '異常投稿',
            self::AiReview => 'AI要確認',
            self::AiHide   => 'AI非表示推奨',
        };
    }
}
