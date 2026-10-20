<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Answer;
use App\Enums\AnswerStatus;
use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Models\AnswerModel;
use App\Models\OogiriModel;

/**
 * 回答管理（A-04）：大喜利をまたいだ回答の一覧と、管理者による操作
 *
 * @phpstan-type AnswerFilter array{q: string, oogiri: string, status: string, reports: string, period: string}
 * @phpstan-type AnswerPage   array{items: list<Answer>, total: int, page: int, perPage: int, pages: int}
 */
final class AnswerAdminService
{
    /**
     * 状態の変更がほかの処理と重なったときにやり直す回数
     */
    private const CHANGE_RETRY = 3;

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

    public function __construct(
        private readonly AnswerModel $answers,
        private readonly OogiriModel $oogiris,
    ) {
    }

    /**
     * 一覧（検索・絞り込み・ページ）。お題は oogiris と JOIN して付ける
     *
     * @param AnswerFilter $filter
     *
     * @return AnswerPage
     */
    public function list(array $filter, int $page): array
    {
        $builder = $this->answers->builder()
            ->select('answers.*, oogiris.odai AS odai')
            ->join('oogiris', 'oogiris.id = answers.oogiri_id');

        if ($filter['status'] !== 'all') {
            $builder->where('answers.status', $filter['status']);
        }
        if ($filter['reports'] !== 'all') {
            $builder->where('answers.report_count >=', (int) $filter['reports']);
        }
        if ($filter['q'] !== '') {
            $builder->like('answers.body', $filter['q']);
        }
        if ($filter['oogiri'] !== '') {
            $o = ltrim($filter['oogiri'], '#');
            $builder->groupStart()->like('oogiris.odai', $o)->orWhere('oogiris.slug', $o);
            if (ctype_digit($o)) {
                $builder->orWhere('oogiris.id', (int) $o);
            }
            $builder->groupEnd();
        }
        OogiriAdminService::applyPeriod($builder, $filter['period'], 'answers.created_at');
        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('answers.created_at', 'DESC')->orderBy('answers.id', 'DESC')->get(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->getResultArray();
        $items = array_map(static fn (array $row) => new Answer($row), $rows);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => self::PER_PAGE, 'pages' => max(1, (int) ceil($total / self::PER_PAGE))];
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): Answer
    {
        $answer = $this->answers->find($id);

        if ($answer === null) {
            throw new NotFoundException();
        }

        return $answer;
    }

    /**
     * 非表示にする（A-04、S-04 の自動処理、AI の自動非表示）。回答数カウンタとキャッシュの整合もここで
     */
    public function hide(int $id, ?string $actor = null, string $what = '非表示にした'): void
    {
        $answer = $this->changeStatus($id, static fn (Answer $a): ?array => $a->status() === AnswerStatus::Visible ? ['status' => AnswerStatus::Hidden->value] : null);

        if ($answer !== null) {
            $this->purge($answer);
            service('adminLog')->record('answer', $id, $what, $actor);
        }
    }

    /**
     * 再公開する
     */
    public function publish(int $id): void
    {
        $answer = $this->changeStatus($id, static fn (Answer $a): ?array => $a->status() === AnswerStatus::Visible ? null : ['status' => AnswerStatus::Visible->value]);

        if ($answer !== null) {
            $this->purge($answer);
            service('adminLog')->record('answer', $id, '再公開');
        }
    }

    /**
     * 削除する（論理削除）
     */
    public function delete(int $id): void
    {
        $answer = $this->changeStatus($id, static fn (Answer $a): ?array => $a->status() === AnswerStatus::Deleted ? null : ['status' => AnswerStatus::Deleted->value, 'rank' => null]);

        if ($answer !== null) {
            $this->purge($answer);
            service('adminLog')->record('answer', $id, '削除');
        }
    }

    /**
     * 公開状態を変え、回答数を合わせる。読んだときの状態のままであることを条件に更新し、変わっていたら読み直してやり直す
     * （同時に2人が非表示にして回答数が2つ減る、といったずれを防ぐ。更新と回答数は同じトランザクション）
     *
     * @param callable(Answer): (array<string, mixed>|null) $change 今の行から更新内容を作る。何もしないなら null
     *
     * @return Answer|null 更新する前の行。何もしなかったら null
     *
     * @throws AppException      何度やっても重なった
     * @throws NotFoundException
     */
    private function changeStatus(int $id, callable $change): ?Answer
    {
        for ($try = 0; $try < self::CHANGE_RETRY; $try++) {
            $answer = $this->find($id);
            $data   = $change($answer);

            if ($data === null) {
                return null;
            }
            $changed = $this->answers->transaction(function () use ($answer, $data): bool {
                $this->answers->where('id', $answer->id)->where('status', $answer->status)->set($data)->update();

                if ($this->answers->affectedRows() !== 1) {
                    return false;
                }
                $wasVisible = $answer->status() === AnswerStatus::Visible;
                $isVisible  = $data['status'] === AnswerStatus::Visible->value;

                if ($wasVisible && ! $isVisible) {
                    $this->oogiris->decrement($answer->oogiri_id, 'answer_count');
                } elseif (! $wasVisible && $isVisible) {
                    $this->oogiris->increment($answer->oogiri_id, 'answer_count');
                }

                return true;
            });

            if ($changed) {
                return $answer;
            }
        }

        throw new AppException(ErrorCode::Conflict);
    }

    /**
     * 公開状態が変わった回答の表示を作り直す：ページキャッシュを捨て、OGP 画像を消す・作り直す（レビュー指摘 7）
     */
    private function purge(Answer $answer): void
    {
        $oogiri = $this->oogiris->find($answer->oogiri_id);

        if ($oogiri !== null) {
            service('pageCache')->purgeOogiri($oogiri->slug);
            service('ogp')->refreshForAnswer($answer, $oogiri);
        }
    }
}
