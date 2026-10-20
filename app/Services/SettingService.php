<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 運用設定（A-10）の読み出し
 *
 * TODO(inner): settings テーブルから読み、キャッシュする。キー名と初期値はここが正。
 */
final class SettingService
{
    /**
     * @var array<string, bool|int|string>
     */
    private const DEFAULTS = [
        'odai_max'          => 100,  // お題の最大文字数
        'answer_max'        => 140,  // 回答の最大文字数
        'open_hours'        => 24,   // 募集時間
        'select_hours'      => 72,   // 結果選出の期限
        'burst_count'       => 3,    // 連続投稿の判定回数
        'burst_window_sec'  => 60,   // 連続投稿の判定時間
        'report_count'      => 3,    // 通報で一時非表示にする件数
        'report_window_min' => 60,
        'ai_enabled'        => true,
        'ai_monthly_limit'  => 10000,
        'ai_auto_hide'      => false,
        'hashtag'           => 'ぼけばこ',
        'tpl_recruit'       => "24時間だけ大喜利やります！\nお題：{odai}\n{url}",
        'tpl_answer'        => "お題：{odai}\n俺の回答：{answer}\n{url}",
        'tpl_result'        => "結果発表！\nお題：{odai}\n1位：{first}\n{url}",
    ];

    /**
     * お題テンプレート（未確定事項9：固定か運用設定かは未決。当面は固定）
     */
    public const ODAI_TEMPLATES = [
        'こんな○○は嫌だ',
        '○○が絶対に言わない一言',
        '100年後の○○あるある',
        '絶対に売れない○○の商品名とは？',
    ];

    public function int(string $key): int
    {
        return (int) self::DEFAULTS[$key];
    }

    public function string(string $key): string
    {
        return (string) self::DEFAULTS[$key];
    }

    public function bool(string $key): bool
    {
        return (bool) self::DEFAULTS[$key];
    }
}
