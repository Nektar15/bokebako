<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminLog;
use App\Enums\OgpKind;
use App\Exceptions\ValidationException;
use App\Models\AdminLogModel;
use App\Models\AiJudgementModel;
use App\Models\SettingModel;
use CodeIgniter\I18n\Time;

/**
 * 運用設定（A-10）の読み出しと保存
 *
 * settings テーブルの値で DEFAULTS を上書きし、キャッシュする（毎リクエストで DB を引かない：FEATURES 0-8）。
 * キー名と初期値はここが正。
 */
final class SettingService
{
    public const CACHE_KEY = 'settings';
    public const CACHE_TTL = 3600;

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
        'tpl_result'        => "結果発表！\nお題：{odai}\n{ranks}\n{url}",
        // OGP 画像に載せる文言と順位（A-08 で編集。ADR 0012）
        'ogp_recruit_pill'  => '24時間限定 回答募集中',
        'ogp_answer_prefix' => 'お題：',
        'ogp_result_stamp'  => '結果発表',
        'ogp_result_prefix' => 'お題：',
        'ogp_result_count'  => '全{count}回答',
        'ogp_result_ranks'  => 3,
    ];

    /**
     * 変更履歴に出す項目名（A-10 の画面の表記。キー名のまま出さない。監査 2026-10-21）
     */
    private const LABELS = [
        'odai_max'          => 'お題の最大文字数',
        'answer_max'        => '回答の最大文字数',
        'open_hours'        => '募集時間',
        'select_hours'      => '結果選出の期限',
        'burst_count'       => '連続投稿の回数',
        'burst_window_sec'  => '連続投稿の判定時間（秒）',
        'limit_method'      => '制限の方法',
        'ban_minutes'       => '停止する時間（分）',
        'report_count'      => '非表示にする通報数',
        'report_window_min' => '通報を数える時間（分）',
        'surge_count'       => '急増の通知',
        'ai_enabled'        => 'AI補助判定',
        'ai_targets'        => '判定する投稿',
        'ai_monthly_limit'  => '月間の上限件数',
        'ai_auto_hide'      => 'AIの判定だけで非表示にする',
        'hashtag'           => 'ハッシュタグ',
        'tpl_recruit'       => '募集シェアの投稿文',
        'tpl_answer'        => '回答シェアの投稿文',
        'tpl_result'        => '結果シェアの投稿文',
    ];

    /**
     * OGP 画像の文言（A-08 で編集する。A-10 の保存では触らない）。キー => [表示名, 最大文字数, 空でもよいか]
     */
    public const OGP_TEXTS = [
        'ogp_recruit_pill'  => ['右上の札', 16, false],
        'ogp_answer_prefix' => ['お題の前につける言葉', 8, true],
        'ogp_result_stamp'  => ['判子', 6, false],
        'ogp_result_prefix' => ['お題の前につける言葉', 8, true],
        'ogp_result_count'  => ['回答数（{count} が数になる）', 16, true],
    ];

    /**
     * 結果用 OGP に載せる順位（値 => 表示名）
     */
    public const OGP_RESULT_RANKS = [1 => '1位だけ', 2 => '1〜2位', 3 => '1〜3位'];

    /**
     * お題テンプレート（未確定事項9：固定か運用設定かは未決。当面は固定）
     */
    public const ODAI_TEMPLATES = [
        'こんな○○は嫌だ',
        '○○が絶対に言わない一言',
        '100年後の○○あるある',
        '絶対に売れない○○の商品名とは？',
    ];

    /**
     * @var array<string, bool|int|list<string>|string>|null
     */
    private ?array $values = null;

    public function int(string $key): int
    {
        return (int) $this->all()[$key];
    }

    public function string(string $key): string
    {
        return (string) $this->all()[$key];
    }

    public function bool(string $key): bool
    {
        return (bool) $this->all()[$key];
    }

    /**
     * @return list<string>
     */
    public function list(string $key): array
    {
        return (array) $this->all()[$key];
    }

    /**
     * すべての設定値（DEFAULTS を settings テーブルの値で上書きしたもの。キャッシュ済み）
     *
     * @return array<string, bool|int|list<string>|string>
     */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }
        $stored = cache(self::CACHE_KEY);

        if (! is_array($stored)) {
            $stored = [];

            foreach ((new SettingModel())->findAll() as $row) {
                $stored[$row['name']] = $row['value'];
            }
            cache()->save(self::CACHE_KEY, $stored, self::CACHE_TTL);
        }
        $values = self::DEFAULTS;

        foreach ($stored as $key => $raw) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            $default      = self::DEFAULTS[$key];
            $values[$key] = match (true) {
                is_bool($default)  => $raw === '1',
                is_int($default)   => (int) $raw,
                is_array($default) => $raw === '' ? [] : explode(',', $raw),
                default            => (string) $raw,
            };
        }

        return $this->values = $values;
    }

    /**
     * キャッシュを捨てる（保存後、テスト）
     */
    public function clearCache(): void
    {
        $this->values = null;
        cache()->delete(self::CACHE_KEY);
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
        return (new AiJudgementModel())
            ->where('queued', 0)
            ->where('verdict !=', 'none')
            ->where('updated_at >=', Time::now()->format('Y-m-01 00:00:00'))
            ->countAllResults();
    }

    /**
     * 変更履歴（新しい順）
     *
     * @return list<AdminLog>
     */
    public function history(): array
    {
        return (new AdminLogModel())->where('target_type', 'setting')->orderBy('created_at', 'DESC')->findAll(20);
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
        $before  = $this->all();
        $model   = new SettingModel();
        $changes = [];

        foreach (self::DEFAULTS as $key => $default) {
            if (str_starts_with($key, 'ogp_')) {
                continue; // OGP の文言は A-08（updateOgp）で保存する
            }
            $value = match (true) {
                is_bool($default)  => ($input[$key] ?? '') === '1',
                is_int($default)   => (int) $input[$key],
                is_array($default) => array_values((array) ($input[$key] ?? [])),
                default            => (string) $input[$key],
            };
            $raw = match (true) {
                is_bool($value)  => $value ? '1' : '0',
                is_array($value) => implode(',', $value),
                default          => (string) $value,
            };
            $row = $model->where('name', $key)->first();
            $row === null ? $model->insert(['name' => $key, 'value' => $raw]) : $model->update($row['id'], ['value' => $raw]);

            if ($before[$key] !== $value) {
                $changes[] = (self::LABELS[$key] ?? $key) . '「' . $this->describe($before[$key]) . '」→「' . $this->describe($value) . '」';
            }
        }
        $this->clearCache();

        if ($changes !== []) {
            service('adminLog')->record('setting', null, implode('、', $changes));
        }
    }

    /**
     * OGP 画像の文言と載せる順位を保存する（A-08。ADR 0012）
     *
     * @param array<string, mixed> $input
     *
     * @throws ValidationException
     */
    public function updateOgp(array $input): void
    {
        $errors = [];
        $values = [];

        foreach (self::OGP_TEXTS as $key => [$label, $max, $optional]) {
            $value = $input[$key] ?? '';
            $value = is_string($value) ? trim((string) preg_replace('/[\r\n\t]+/u', ' ', $value)) : '';

            if ($value === '' && ! $optional) {
                $errors[$key] = '入力してください';
            } elseif (mb_strlen($value) > $max) {
                $errors[$key] = "{$max}文字以内で入力してください";
            }
            $values[$key] = $value;
        }
        $ranks = $input['ogp_result_ranks'] ?? '';

        if (! is_string($ranks) || ! isset(self::OGP_RESULT_RANKS[(int) $ranks]) || ! ctype_digit($ranks)) {
            $errors['ogp_result_ranks'] = '載せる順位を選んでください';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        $values['ogp_result_ranks'] = (int) $ranks;
        $before                     = $this->all();
        $model                      = new SettingModel();
        $changes                    = [];

        foreach ($values as $key => $value) {
            $row = $model->where('name', $key)->first();
            $row === null ? $model->insert(['name' => $key, 'value' => (string) $value]) : $model->update($row['id'], ['value' => (string) $value]);

            if ($before[$key] !== $value) {
                $kind      = OgpKind::from(explode('_', $key)[1]);
                $label     = $kind->label() . '：' . ($key === 'ogp_result_ranks' ? '載せる順位' : self::OGP_TEXTS[$key][0]);
                $changes[] = 'OGP ' . $label . '「' . $this->describe($before[$key]) . '」→「' . $this->describe($value) . '」';
            }
        }
        $this->clearCache();

        if ($changes !== []) {
            service('adminLog')->record('setting', null, implode('、', $changes));
        }
    }

    /**
     * OGP 画像に載せる文言（OgpImage に渡す形）
     *
     * @return array{recruit_pill: string, answer_prefix: string, result_stamp: string, result_prefix: string, result_count: string, result_ranks: int}
     */
    public function ogpTexts(): array
    {
        $all = $this->all();

        return [
            'recruit_pill'  => (string) $all['ogp_recruit_pill'],
            'answer_prefix' => (string) $all['ogp_answer_prefix'],
            'result_stamp'  => (string) $all['ogp_result_stamp'],
            'result_prefix' => (string) $all['ogp_result_prefix'],
            'result_count'  => (string) $all['ogp_result_count'],
            'result_ranks'  => max(1, min(3, (int) $all['ogp_result_ranks'])),
        ];
    }

    /**
     * 変更履歴に書く値の表記
     *
     * @param bool|int|list<string>|string $value
     */
    private function describe(array|bool|int|string $value): string
    {
        return match (true) {
            is_bool($value)  => $value ? 'ON' : 'OFF',
            is_array($value) => implode('/', $value),
            is_int($value)   => number_format($value),
            default          => str_replace("\n", ' ', $value),
        };
    }
}
