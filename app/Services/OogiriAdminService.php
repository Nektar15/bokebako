<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminLog;
use App\Entities\Answer;
use App\Entities\Oogiri;
use App\Enums\OogiriStatus;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\AdminOogiriFixture;
use App\Services\Fixtures\OogiriFixture;

/**
 * 大喜利管理（A-03）：一覧・詳細の取得と、管理者による操作
 *
 * @phpstan-type OogiriFilter array{q: string, period: string, sort: string, status: string}
 * @phpstan-type OogiriPage   array{items: list<Oogiri>, total: int, page: int, perPage: int, pages: int}
 */
final class OogiriAdminService
{
    public const PER_PAGE = 20;

    /**
     * 一覧で通報数を強調する下限（見本の値。運用設定にはしない）
     */
    public const REPORT_ALERT = 5;

    /**
     * 並び替えの選択肢（値 => 表示名）。SPEC A-03
     */
    public const SORTS = [
        'new'     => '新着順',
        'pv'      => 'PV順',
        'answers' => '回答数順',
        'derived' => '派生開催数順',
        'reports' => '通報数順',
    ];

    /**
     * 期間の選択肢（値 => 表示名）。A-03・A-04 共通
     */
    public const PERIODS = [
        'all'    => '期間：すべて',
        'today'  => '今日',
        '7d'     => '7日',
        '30d'    => '30日',
        'custom' => '期間指定',
    ];

    /**
     * 一覧（検索・並び替え・状態・ページ）
     *
     * @param OogiriFilter $filter
     *
     * @return OogiriPage
     */
    public function list(array $filter, int $page): array
    {
        // TODO(inner): OogiriModel で検索する（LIMIT 必須、通報数・シェア数・派生開催数は集計カラムから）。戻り値の形は変えない
        $items = AdminOogiriFixture::all();

        if ($filter['status'] !== 'all') {
            $items = array_filter($items, static fn (Oogiri $o) => $o->status()->value === $filter['status']);
        }
        if ($filter['q'] !== '') {
            $items = array_filter($items, static fn (Oogiri $o) => str_contains($o->odai, $filter['q']) || str_contains((string) $o->id, ltrim($filter['q'], '#')));
        }
        $items = array_values($items);
        usort($items, static fn (Oogiri $a, Oogiri $b) => match ($filter['sort']) {
            'pv'      => $b->pv <=> $a->pv,
            'answers' => $b->answer_count <=> $a->answer_count,
            'derived' => $b->derived_count <=> $a->derived_count,
            'reports' => $b->report_count <=> $a->report_count,
            default   => $b->created_at <=> $a->created_at,
        });

        // 外側フェーズ：件数はタブの見本の数字に合わせる（ページ送りの見た目を確認するため）
        $total = $filter['q'] === '' ? (AdminOogiriFixture::counts()[$filter['status']] ?? count($items)) : count($items);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => self::PER_PAGE, 'pages' => max(1, (int) ceil($total / self::PER_PAGE))];
    }

    /**
     * 状態タブの件数
     *
     * @return array<string, int> 'all' または OogiriStatus の値 => 件数
     */
    public function counts(): array
    {
        // TODO(inner): status ごとに COUNT する
        return AdminOogiriFixture::counts();
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): Oogiri
    {
        // TODO(inner): OogiriModel から取得する。削除済みも返す（詳細画面で見るため）
        foreach (AdminOogiriFixture::all() as $oogiri) {
            if ($oogiri->id === $id) {
                return $oogiri;
            }
        }

        throw new NotFoundException();
    }

    /**
     * 詳細画面に出す回答（新しい順、先頭の数件）
     *
     * @return list<Answer>
     */
    public function answers(Oogiri $oogiri): array
    {
        // TODO(inner): AnswerModel から oogiri_id で取る（非表示・削除済みも含める）
        $name = match ($oogiri->status()) {
            OogiriStatus::Decided  => 'decided',
            OogiriStatus::NoResult => 'no_result',
            default                => 'open',
        };
        $answers = OogiriFixture::answers($name, $oogiri->id);

        foreach ($answers as $answer) {
            $answer->report_count = $answer->id === 90409 ? 1 : 0;
        }

        return array_slice($answers, 0, 5);
    }

    /**
     * ベスト回答（rank 順）
     *
     * @return list<Answer>
     */
    public function ranked(Oogiri $oogiri): array
    {
        $ranked = array_filter($this->answers($oogiri), static fn (Answer $a) => $a->rank !== null);
        usort($ranked, static fn (Answer $a, Answer $b) => $a->rank <=> $b->rank);

        return array_values($ranked);
    }

    /**
     * 操作履歴（新しい順）
     *
     * @return list<AdminLog>
     */
    public function logs(Oogiri $oogiri): array
    {
        // TODO(inner): admin_logs から対象の大喜利の分を取る
        return AdminOogiriFixture::logs($oogiri);
    }

    /**
     * 非表示にする（A-03）
     */
    public function hide(int $id): void
    {
        $this->find($id);
        // TODO(inner): status を hidden に、ページキャッシュの破棄、操作履歴の記録
    }

    /**
     * 再公開する（A-03）
     */
    public function publish(int $id): void
    {
        $this->find($id);
        // TODO(inner): status を元の状態（終了日時から判定）に戻す、キャッシュ再生成、操作履歴の記録
    }

    /**
     * 削除する（A-03）
     */
    public function delete(int $id): void
    {
        $this->find($id);
        // TODO(inner): status を deleted に、キャッシュ破棄、操作履歴の記録。物理削除はしない
    }

    /**
     * 開催者の代理でベスト回答を変える（A-03 詳細。誤操作対応用）
     *
     * @param list<int|string> $answerIds 1位から順。空欄（0）は「なし」
     *
     * @throws ValidationException
     */
    public function decide(int $id, array $answerIds): void
    {
        $this->find($id);
        $ids = array_values(array_unique(array_filter(array_map('intval', $answerIds))));

        if ($ids === [] || count($ids) > 3) {
            throw new ValidationException(['ranks' => 'ベスト回答を1〜3件選んでください']);
        }
        // TODO(inner): 順位の保存、結果用OGPとページキャッシュの再生成、操作履歴の記録
    }

    /**
     * 結果ページのキャッシュを再生成する（A-03 詳細）
     */
    public function regenerateCache(int $id): void
    {
        $this->find($id);
        // TODO(inner): ページキャッシュの破棄と再生成、操作履歴の記録
    }
}
