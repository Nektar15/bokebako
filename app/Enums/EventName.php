<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 計測イベントの名前（S-06 の表）。GA4 にも同名で送る
 */
enum EventName: string
{
    case Create       = 'create';        // 開催
    case ShareRecruit = 'share_recruit'; // 募集シェア
    case Visit        = 'visit';         // 募集URL流入
    case Answer       = 'answer';        // 回答
    case ShareAnswer  = 'share_answer';  // 回答シェア
    case ClickCreate  = 'click_create';  // 新規開催クリック
    case Decide       = 'decide';        // 結果確定
    case ShareResult  = 'share_result';  // 結果シェア
    case PageView     = 'pv';            // PV（ページキャッシュ配信分は JS から送る）

    /**
     * JS（ブラウザ）から送ってよいイベントか。それ以外はサーバー側だけが記録する
     */
    public function allowedFromBrowser(): bool
    {
        return in_array($this, [self::ShareRecruit, self::ShareAnswer, self::ShareResult, self::ClickCreate, self::PageView], true);
    }
}
