<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\NgWord;
use App\Enums\NgWordAction;
use App\Enums\NgWordMatchType;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\NgWordModel;

/**
 * NGワード管理（A-06）：一覧・追加・編集・削除・CSV
 *
 * @phpstan-type NgWordFilter array{q: string, action: string, category: string}
 * @phpstan-type NgWordInput  array{word?: string, match_type?: string, category?: string, action?: string, enabled?: string}
 */
final class NgWordService
{
    /**
     * CSV の列（A-06 の注記どおり）
     */
    public const CSV_COLUMNS = ['word', 'match_type', 'category', 'action', 'enabled'];

    /**
     * カテゴリーの一覧（固定。未確定事項11、ADR 0003）
     */
    public const CATEGORIES = ['誹謗中傷', '個人情報', 'いじめ・学校', '脅迫', '性的', 'スパム', 'その他'];

    public const CACHE_KEY = 'ng_words';

    public function __construct(private readonly NgWordModel $model)
    {
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * 投稿本文を判定する（FEATURES 0-3）。有効なワードはキャッシュして毎回 DB を引かない
     *
     * @return array{action: NgWordAction|null, detail: string} action は最も強い処理（投稿拒否 > 要確認）。一致なしは null
     */
    public function check(string $text): array
    {
        $words = cache(self::CACHE_KEY);

        if (! is_array($words)) {
            $words = array_map(static fn (NgWord $w) => ['word' => $w->word, 'match_type' => $w->match_type, 'action' => $w->action], $this->model->where('enabled', 1)->findAll());
            cache()->save(self::CACHE_KEY, $words, 3600);
        }
        $action = null;
        $hits   = [];

        foreach ($words as $w) {
            $matched = match (NgWordMatchType::from($w['match_type'])) {
                NgWordMatchType::Exact   => trim($text) === $w['word'],
                NgWordMatchType::Partial => mb_stripos($text, $w['word']) !== false,
                NgWordMatchType::Regex   => $this->isValidRegex($w['word']) && preg_match('/' . str_replace('/', '\\/', $w['word']) . '/u', $text) === 1,
            };

            if (! $matched) {
                continue;
            }
            $wordAction = NgWordAction::from($w['action']);
            $hits[]     = '「' . $w['word'] . '」（' . NgWordMatchType::from($w['match_type'])->label() . '・' . $wordAction->label() . '）';

            if ($action === null || $wordAction === NgWordAction::Reject) {
                $action = $wordAction;
            }
        }

        return ['action' => $action, 'detail' => implode('、', $hits)];
    }

    /**
     * 一覧（検索・絞り込み。件数が少ないのでページングしない）
     *
     * @param NgWordFilter $filter
     *
     * @return list<NgWord>
     */
    public function list(array $filter): array
    {
        $items = $this->model->orderBy('id', 'ASC')->findAll();

        if ($filter['action'] !== 'all') {
            $items = array_filter($items, static fn (NgWord $w) => $w->action()->value === $filter['action']);
        }
        if ($filter['category'] !== 'all') {
            $items = array_filter($items, static fn (NgWord $w) => $w->category === $filter['category']);
        }
        if ($filter['q'] !== '') {
            $items = array_filter($items, static fn (NgWord $w) => str_contains($w->word, $filter['q']));
        }

        return array_values($items);
    }

    /**
     * 有効な件数と全件数
     *
     * @return array{enabled: int, total: int}
     */
    public function counts(): array
    {
        return ['enabled' => $this->model->where('enabled', 1)->countAllResults(), 'total' => $this->model->countAllResults()];
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): NgWord
    {
        $word = $this->model->find($id);

        if ($word === null) {
            throw new NotFoundException();
        }

        return $word;
    }

    /**
     * 追加する
     *
     * @param NgWordInput $input
     *
     * @throws ValidationException
     */
    public function create(array $input): void
    {
        $this->validate($input);
        $this->save(null, $input);
        service('adminLog')->record('ng_word', null, 'NGワードを追加：' . trim((string) $input['word']));
    }

    /**
     * 編集する（有効／無効の切り替えもここ）
     *
     * @param NgWordInput $input
     *
     * @throws ValidationException
     */
    public function update(int $id, array $input): void
    {
        $word = $this->find($id);
        $this->validate($input, $id);
        $this->save($id, $input);
        $enabled = ($input['enabled'] ?? '1') === '1';
        service('adminLog')->record('ng_word', $id, 'NGワードを' . ($word->enabled !== $enabled ? ($enabled ? '有効' : '無効') : '編集') . '：' . trim((string) $input['word']));
    }

    public function delete(int $id): void
    {
        $word = $this->find($id);
        $this->model->delete($id);
        cache()->delete(self::CACHE_KEY);
        service('adminLog')->record('ng_word', $id, 'NGワードを削除：' . $word->word);
    }

    /**
     * @param NgWordInput $input
     */
    private function save(?int $id, array $input): void
    {
        $row = [
            'word'       => trim((string) $input['word']),
            'match_type' => (string) $input['match_type'],
            'category'   => (string) $input['category'],
            'action'     => (string) $input['action'],
            'enabled'    => ($input['enabled'] ?? '1') === '1' ? 1 : 0,
        ];
        $id === null ? $this->model->insert($row) : $this->model->update($id, $row);
        cache()->delete(self::CACHE_KEY);
    }

    /**
     * CSV の本文（UTF-8、ヘッダー行つき）
     */
    public function exportCsv(): string
    {
        $lines = [implode(',', self::CSV_COLUMNS)];

        foreach ($this->model->orderBy('id', 'ASC')->findAll() as $word) {
            $lines[] = implode(',', array_map(
                static fn (string $v) => '"' . str_replace('"', '""', $v) . '"',
                [$word->word, $word->match_type, $word->category, $word->action, $word->enabled ? '1' : '0'],
            ));
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * CSV を取り込む。同じワード・一致方式の行は上書き
     *
     * @return int 取り込んだ行数
     *
     * @throws ValidationException
     */
    public function importCsv(string $csv): int
    {
        $rows = array_filter(array_map('trim', explode("\n", $csv)), static fn (string $l) => $l !== '');

        if (count($rows) < 2) {
            throw new ValidationException(['csv' => 'CSV にデータ行がありません']);
        }
        $count = 0;

        foreach (array_slice(array_values($rows), 1) as $line) {
            $cols = array_map('trim', str_getcsv($line, ',', '"', '\\'));

            if (count($cols) < 5) {
                throw new ValidationException(['csv' => '列が足りない行があります：' . mb_substr($line, 0, 40)]);
            }
            $input    = array_combine(self::CSV_COLUMNS, array_slice($cols, 0, 5));
            $existing = $this->model->where('word', $input['word'])->where('match_type', $input['match_type'])->first();
            $this->validate($input, $existing?->id);
            $this->save($existing?->id, $input);
            $count++;
        }
        service('adminLog')->record('ng_word', null, "CSV から {$count} 行を取り込み");

        return $count;
    }

    /**
     * @param NgWordInput $input
     *
     * @throws ValidationException
     */
    private function validate(array $input, ?int $id = null): void
    {
        $errors = [];
        $word   = trim((string) ($input['word'] ?? ''));
        $match  = NgWordMatchType::tryFrom((string) ($input['match_type'] ?? ''));

        if ($word === '') {
            $errors['word'] = 'ワードを入力してください';
        }
        if ($match === null) {
            $errors['match_type'] = '一致方式を選んでください';
        } elseif ($match === NgWordMatchType::Regex && $word !== '' && ! $this->isValidRegex($word)) {
            $errors['word'] = '正しい正規表現ではありません';
        }
        if (! in_array((string) ($input['category'] ?? ''), $this->categories(), true)) {
            $errors['category'] = 'カテゴリーを選んでください';
        }
        if (NgWordAction::tryFrom((string) ($input['action'] ?? '')) === null) {
            $errors['action'] = '一致したときの処理を選んでください';
        }
        if ($word !== '' && $match !== null && ! isset($errors['word'])) {
            $dup = $this->model->where('word', $word)->where('match_type', $match->value);
            if ($id !== null) {
                $dup->where('id !=', $id);
            }
            if ($dup->countAllResults() > 0) {
                $errors['word'] = '同じワード・一致方式がすでに登録されています';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * 正規表現として使えるか（前後の / は不要。不正なら preg_match が警告を出すので、警告を拾って false にする）
     */
    private function isValidRegex(string $pattern): bool
    {
        $valid = true;
        set_error_handler(static function () use (&$valid): bool {
            $valid = false;

            return true;
        });

        try {
            return preg_match('/' . str_replace('/', '\\/', $pattern) . '/u', '') !== false && $valid;
        } finally {
            restore_error_handler();
        }
    }
}
