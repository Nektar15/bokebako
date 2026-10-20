<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminLog;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\SettingFixture;

/**
 * 運用設定（A-10）の読み出しと保存
 *
 * TODO(inner): settings テーブルから読み、キャッシュする。キー名と初期値はここが正。
 */
final class SettingService
{
    /**
     * 連続投稿の制限方法（値 => 表示名）。SPEC S-03
     */
    public const LIMIT_METHODS = [
        'temporary' => '一時的に投稿を止める',
        'captcha'   => 'CAPTCHAを表示する',
        'ban'       => '一定時間、投稿を停止する',
    ];

    /**
     * AI補助判定の対象（値 => 表示名）。SPEC S-05
     */
    public const AI_TARGETS = [
        'ng_review' => 'NGワード「要確認」',
        'reported'  => '通報あり',
        'abnormal'  => '異常投稿',
    ];

    /**
     * 数値の設定の上限・下限（A-10 の検証用）。キー => [最小, 最大]
     */
    private const INT_RANGES = [
        'odai_max'          => [10, 500],
        'answer_max'        => [10, 1000],
        'open_hours'        => [1, 168],
        'select_hours'      => [1, 720],
        'burst_count'       => [1, 100],
        'burst_window_sec'  => [1, 3600],
        'ban_minutes'       => [1, 10080],
        'report_count'      => [1, 100],
        'report_window_min' => [1, 1440],
        'surge_count'       => [1, 1000],
        'ai_monthly_limit'  => [0, 1000000],
    ];

    /**
     * @var array<string, bool|int|list<string>|string>
     */
    private const DEFAULTS = [
        'odai_max'          => 100,  // お題の最大文字数
        'answer_max'        => 140,  // 回答の最大文字数
        'open_hours'        => 24,   // 募集時間
        'select_hours'      => 72,   // 結果選出の期限
        'burst_count'       => 3,    // 連続投稿の判定回数
        'burst_window_sec'  => 60,   // 連続投稿の判定時間
        'limit_method'      => 'temporary', // 連続投稿の制限方法（LIMIT_METHODS）
        'ban_minutes'       => 30,   // 「一定時間、投稿を停止する」の長さ
        'report_count'      => 3,    // 通報で一時非表示にする件数
        'report_window_min' => 60,
        'surge_count'       => 10,   // 1時間にこの件数を超えたら通知をまとめる
        'ai_enabled'        => true,
        'ai_targets'        => ['ng_review', 'reported', 'abnormal'], // AI_TARGETS のキー
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

    /**
     * @return list<string>
     */
    public function list(string $key): array
    {
        return (array) self::DEFAULTS[$key];
    }

    /**
     * すべての設定値（A-10 のフォームの初期値）
     *
     * @return array<string, bool|int|list<string>|string>
     */
    public function all(): array
    {
        // TODO(inner): settings テーブルの値で DEFAULTS を上書きして返す
        return self::DEFAULTS;
    }

    /**
     * 初期値（A-10 の「初期値 …」の表示用）
     *
     * @return array<string, bool|int|list<string>|string>
     */
    public function defaults(): array
    {
        return self::DEFAULTS;
    }

    /**
     * AI補助判定の今月の使用件数
     */
    public function aiMonthlyUsed(): int
    {
        // TODO(inner): 今月の AI 判定件数を数える
        return 1284;
    }

    /**
     * 変更履歴（新しい順）
     *
     * @return list<AdminLog>
     */
    public function history(): array
    {
        // TODO(inner): admin_logs から設定変更の分を取る
        return SettingFixture::history();
    }

    /**
     * 保存する。値はすべて文字列で届く（フォーム）
     *
     * @param array<string, mixed> $input
     *
     * @throws ValidationException
     */
    public function update(array $input): void
    {
        $errors = [];

        foreach (self::INT_RANGES as $key => [$min, $max]) {
            $value = $input[$key] ?? '';

            if (! is_string($value) || ! ctype_digit($value) || (int) $value < $min || (int) $value > $max) {
                $errors[$key] = "{$min}〜{$max} の整数で入力してください";
            }
        }
        if (! isset(self::LIMIT_METHODS[(string) ($input['limit_method'] ?? '')])) {
            $errors['limit_method'] = '制限の方法を選んでください';
        }

        foreach ((array) ($input['ai_targets'] ?? []) as $target) {
            if (! isset(self::AI_TARGETS[$target])) {
                $errors['ai_targets'] = '判定する投稿の選択が正しくありません';
            }
        }
        if (trim((string) ($input['hashtag'] ?? '')) === '' || str_contains((string) $input['hashtag'], '#')) {
            $errors['hashtag'] = 'ハッシュタグを # なしで入力してください';
        }

        foreach (['tpl_recruit', 'tpl_answer', 'tpl_result'] as $key) {
            if (! str_contains((string) ($input[$key] ?? ''), '{url}')) {
                $errors[$key] = '投稿文には {url} を含めてください';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        // TODO(inner): settings テーブルへ保存、設定キャッシュの破棄、変更内容を操作履歴に記録
    }
}
