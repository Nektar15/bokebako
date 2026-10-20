<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminLog;
use App\Entities\Answer;
use App\Entities\Oogiri;
use App\Enums\ErrorCode;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\AnswerModel;
use App\Models\OogiriModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\I18n\Time;

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
     * 管理者がベスト回答を変更できる状態
     */
    public const DECIDABLE_BY_ADMIN = [OogiriStatus::Open, OogiriStatus::AwaitingSelection, OogiriStatus::Decided, OogiriStatus::NoResult];

    public function __construct(
        private readonly OogiriModel $oogiris,
        private readonly AnswerModel $answers,
        private readonly LifecycleService $lifecycle,
    ) {
    }

    /**
     * 一覧（検索・並び替え・状態・ページ）
     *
     * @param OogiriFilter $filter
     *
     * @return OogiriPage
     */
    public function list(array $filter, int $page): array
    {
        $builder = $this->oogiris->builder();

        if ($filter['status'] !== 'all') {
            $builder->where('status', $filter['status']);
        }
        if ($filter['q'] !== '') {
            $q = ltrim($filter['q'], '#');
            $builder->groupStart()->like('odai', $q);
            if (ctype_digit($q)) {
                $builder->orWhere('id', (int) $q);
            }
            $builder->orWhere('slug', $q)->groupEnd();
        }
        self::applyPeriod($builder, $filter['period']);
        $total = $builder->countAllResults(false);
        $builder->orderBy(match ($filter['sort']) {
            'pv'      => 'pv',
            'answers' => 'answer_count',
            'derived' => 'derived_count',
            'reports' => 'report_count',
            default   => 'created_at',
        }, 'DESC')->orderBy('id', 'DESC');
        $rows  = $builder->get(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->getResultArray();
        $items = array_map(static fn (array $row) => new Oogiri($row), $rows);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => self::PER_PAGE, 'pages' => max(1, (int) ceil($total / self::PER_PAGE))];
    }

    /**
     * 期間の絞り込み（A-03・A-04 共通）
     *
     * @param BaseBuilder $builder
     */
    public static function applyPeriod($builder, string $period, string $column = 'created_at'): void
    {
        $from = match ($period) {
            'today' => Time::now()->setTime(0, 0),
            '7d'    => Time::now()->subDays(7),
            '30d'   => Time::now()->subDays(30),
            default => null,
        };
        if ($from !== null) {
            $builder->where($column . ' >=', $from->toDateTimeString());
        }
    }

    /**
     * 状態タブの件数
     *
     * @return array<string, int> 'all' または OogiriStatus の値 => 件数
     */
    public function counts(): array
    {
        $counts = ['all' => 0];

        foreach (OogiriStatus::cases() as $case) {
            $counts[$case->value] = 0;
        }

        foreach ($this->oogiris->builder()->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $row) {
            $counts[$row['status']] = (int) $row['n'];
            $counts['all'] += (int) $row['n'];
        }

        return $counts;
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): Oogiri
    {
        $oogiri = $this->oogiris->find($id);

        if ($oogiri === null) {
            throw new NotFoundException();
        }

        return $oogiri;
    }

    /**
     * 詳細画面に出す回答（新しい順、先頭の数件。非表示・削除済みも含む）
     *
     * @return list<Answer>
     */
    public function answers(Oogiri $oogiri, int $limit = 20): array
    {
        return $this->answers->where('oogiri_id', $oogiri->id)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll($limit);
    }

    /**
     * ベスト回答（rank 順）
     *
     * @return list<Answer>
     */
    public function ranked(Oogiri $oogiri): array
    {
        return $this->answers->where('oogiri_id', $oogiri->id)->where('rank IS NOT NULL')->orderBy('rank', 'ASC')->findAll(3);
    }

    /**
     * 操作履歴（新しい順）
     *
     * @return list<AdminLog>
     */
    public function logs(Oogiri $oogiri): array
    {
        return service('adminLog')->listFor('oogiri', $oogiri->id);
    }

    /**
     * 非表示にする（A-03、S-04 の自動処理、AI の自動非表示）
     */
    public function hide(int $id, ?string $actor = null, string $what = '非表示にした'): void
    {
        $oogiri = $this->find($id);

        if (in_array($oogiri->status(), [OogiriStatus::Hidden, OogiriStatus::Deleted], true)) {
            return;
        }
        $this->oogiris->update($id, ['status' => OogiriStatus::Hidden->value, 'status_before_hide' => $oogiri->status]);
        service('pageCache')->purgeOogiri($oogiri->slug);
        service('ogp')->removeOogiri($oogiri);
        service('adminLog')->record('oogiri', $id, $what, $actor);
    }

    /**
     * 再公開する（非表示にする前の状態へ）
     *
     * 終了日時を過ぎた募集中は結果選択待ちにし、S-01 と同じ選出期限を入れる（レビュー指摘 6）。
     * 選出期限もすでに過ぎていれば、次の S-02（cron 1分間隔）が結果未選出にする（ADR 0006-5）
     */
    public function publish(int $id): void
    {
        $oogiri = $this->find($id);
        $status = OogiriStatus::tryFrom((string) $oogiri->status_before_hide) ?? OogiriStatus::Open;
        $data   = ['status_before_hide' => null];

        if ($status === OogiriStatus::Open && Time::now()->isAfter($oogiri->ends_at)) {
            $status = OogiriStatus::AwaitingSelection;
        }
        if ($status === OogiriStatus::AwaitingSelection && $oogiri->selection_deadline === null) {
            $data['selection_deadline'] = $this->lifecycle->selectionDeadline($oogiri)->toDateTimeString();
        }
        $this->oogiris->update($id, ['status' => $status->value] + $data);
        service('pageCache')->purgeOogiri($oogiri->slug);
        service('ogp')->restoreOogiri($this->find($id));
        service('adminLog')->record('oogiri', $id, '再公開（' . $status->label() . '）');
    }

    /**
     * 削除する（論理削除。物理削除はしない）
     */
    public function delete(int $id): void
    {
        $oogiri = $this->find($id);
        $this->oogiris->update($id, ['status' => OogiriStatus::Deleted->value]);
        service('pageCache')->purgeOogiri($oogiri->slug);
        service('ogp')->removeOogiri($oogiri);
        service('adminLog')->record('oogiri', $id, '削除');
    }

    /**
     * 開催者の代理でベスト回答を変える（A-03 詳細。誤操作対応用）
     *
     * 回答の検証と状態の条件付き更新は開催者の確定と共通（HostService::saveRanks）。
     * 状態は読み込んだときのまま変わっていないことを条件にする（非表示・削除と同時に操作されたら拒否）
     *
     * @param list<int|string> $answerIds 1位から順。空欄（0）は「なし」
     *
     * @throws AppException        状態が変わっていた、ベスト回答を選べない状態
     * @throws ValidationException 選択なし、選べない回答を含む
     */
    public function decide(int $id, array $answerIds): void
    {
        $oogiri = $this->find($id);

        if (! in_array($oogiri->status(), self::DECIDABLE_BY_ADMIN, true)) {
            throw new AppException(ErrorCode::Forbidden, '非表示・削除中の大喜利のベスト回答は変更できません');
        }
        $ids   = HostService::normalizeIds($answerIds);
        $first = service('host')->saveRanks($oogiri, $ids, [$oogiri->status()]);
        $actor = service('currentAdmin')->get()->name;
        service('ogp')->generateResult($this->find($id), $first, $actor);
        service('adminLog')->record('oogiri', $id, '代理でベスト回答を変更（' . count($ids) . '件）');
    }

    /**
     * 結果ページのキャッシュを再生成する（捨てて、次のアクセスで作り直す）
     */
    public function regenerateCache(int $id): void
    {
        $oogiri = $this->find($id);
        service('pageCache')->purgeOogiri($oogiri->slug);
        service('adminLog')->record('oogiri', $id, 'ページキャッシュを再生成');
    }
}
