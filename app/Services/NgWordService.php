<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\NgWord;
use App\Enums\NgWordAction;
use App\Enums\NgWordMatchType;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\NgWordFixture;

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
     * @return list<string>
     */
    public function categories(): array
    {
        // TODO(inner): 未確定事項11（カテゴリーの管理方法）が決まるまで固定
        return NgWordFixture::CATEGORIES;
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
        // TODO(inner): NgWordModel から取る。戻り値の形は変えない
        $items = NgWordFixture::all();

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
        $all = NgWordFixture::all();

        return ['enabled' => count(array_filter($all, static fn (NgWord $w) => $w->enabled)), 'total' => count($all)];
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): NgWord
    {
        foreach (NgWordFixture::all() as $word) {
            if ($word->id === $id) {
                return $word;
            }
        }

        throw new NotFoundException();
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
        // TODO(inner): 保存、判定用キャッシュの更新、操作履歴の記録
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
        $this->find($id);
        $this->validate($input);
        // TODO(inner): 保存、判定用キャッシュの更新、操作履歴の記録
    }

    public function delete(int $id): void
    {
        $this->find($id);
        // TODO(inner): 削除、判定用キャッシュの更新、操作履歴の記録
    }

    /**
     * CSV の本文（UTF-8、ヘッダー行つき）
     */
    public function exportCsv(): string
    {
        $lines = [implode(',', self::CSV_COLUMNS)];

        foreach (NgWordFixture::all() as $word) {
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

        // TODO(inner): 各行を validate() にかけて保存、判定用キャッシュの更新、操作履歴の記録
        return count($rows) - 1;
    }

    /**
     * @param NgWordInput $input
     *
     * @throws ValidationException
     */
    private function validate(array $input): void
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
