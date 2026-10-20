<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Answer;
use App\Exceptions\NotFoundException;
use App\Services\Fixtures\AdminAnswerFixture;

/**
 * 回答管理（A-04）：大喜利をまたいだ回答の一覧と、管理者による操作
 *
 * @phpstan-type AnswerFilter array{q: string, oogiri: string, status: string, reports: string, period: string}
 * @phpstan-type AnswerPage   array{items: list<Answer>, total: int, page: int, perPage: int, pages: int}
 */
final class AnswerAdminService
{
    public const PER_PAGE = 20;

    /**
     * 一覧で通報数を強調する下限（見本の値。運用設定にはしない）
     */
    public const REPORT_ALERT = 3;

    /**
     * 通報数の絞り込み（値 => 表示名）
     */
    public const REPORT_FILTERS = [
        'all' => '通報：すべて',
        '1'   => '1件以上',
        '3'   => '3件以上',
    ];

    /**
     * 一覧（検索・絞り込み・ページ）
     *
     * @param AnswerFilter $filter
     *
     * @return AnswerPage
     */
    public function list(array $filter, int $page): array
    {
        // TODO(inner): AnswerModel で検索する（oogiris と JOIN してお題を付ける。LIMIT 必須）。戻り値の形は変えない
        $items = AdminAnswerFixture::all();

        if ($filter['status'] !== 'all') {
            $items = array_filter($items, static fn (Answer $a) => $a->status()->value === $filter['status']);
        }
        if ($filter['reports'] !== 'all') {
            $items = array_filter($items, static fn (Answer $a) => $a->report_count >= (int) $filter['reports']);
        }
        if ($filter['q'] !== '') {
            $items = array_filter($items, static fn (Answer $a) => str_contains($a->body, $filter['q']));
        }
        if ($filter['oogiri'] !== '') {
            $items = array_filter($items, static fn (Answer $a) => str_contains($a->odai, $filter['oogiri']) || (string) $a->oogiri_id === ltrim($filter['oogiri'], '#'));
        }
        $items = array_values($items);

        // 外側フェーズ：件数は見本の数字に合わせる（ページ送りの見た目を確認するため）
        $total = $filter === ['q' => '', 'oogiri' => '', 'status' => 'all', 'reports' => 'all', 'period' => 'all'] ? 402118 : count($items);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => self::PER_PAGE, 'pages' => max(1, (int) ceil($total / self::PER_PAGE))];
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): Answer
    {
        // TODO(inner): AnswerModel から取得する
        foreach (AdminAnswerFixture::all() as $answer) {
            if ($answer->id === $id) {
                return $answer;
            }
        }

        throw new NotFoundException();
    }

    /**
     * 非表示にする（A-04）
     */
    public function hide(int $id): void
    {
        $this->find($id);
        // TODO(inner): status を hidden に、回答数カウンタの減算、キャッシュ破棄、操作履歴の記録
    }

    /**
     * 再公開する（A-04）
     */
    public function publish(int $id): void
    {
        $this->find($id);
        // TODO(inner): status を visible に、回答数カウンタの加算、キャッシュ破棄、操作履歴の記録
    }

    /**
     * 削除する（A-04）
     */
    public function delete(int $id): void
    {
        $this->find($id);
        // TODO(inner): status を deleted に、回答数カウンタの整合、キャッシュ破棄、操作履歴の記録。物理削除はしない
    }
}
