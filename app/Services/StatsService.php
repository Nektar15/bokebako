<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AnswerStatus;
use App\Enums\EventName;
use App\Enums\OogiriStatus;
use App\Enums\StatsPeriod;
use App\Models\AiJudgementModel;
use App\Models\AnswerModel;
use App\Models\DailyStatModel;
use App\Models\EventModel;
use App\Models\InquiryModel;
use App\Models\OogiriModel;
use App\Models\ReportModel;
use CodeIgniter\I18n\Time;

/**
 * ダッシュボード（A-02）の集計値と、日次集計（S-06）、管理画面共通の未処理件数
 *
 * ダッシュボードは daily_stats だけを読む（生ログ events は直接集計しない：SPEC 9）。
 *
 * @phpstan-type Summary array{
 *                       pv: int, pvTotal: int, oogiris: int, oogirisTotal: int, answers: int, answersTotal: int, answersPerOogiri: float,
 *                       shareRecruit: int, shareAnswer: int, shareResult: int, reports: int, hidden: int,
 *                       answerers: int, converted: int, conversionRate: float, derived: int, derivedCoefficient: float
 *                       }
 */
final class StatsService
{
    /**
     * 生ログ（events）を残す日数。日次集計のあとはダッシュボードが daily_stats だけを見るので、転換率の計算に要る分だけ残す
     */
    public const EVENT_KEEP_DAYS = 90;

    /**
     * 「期間指定」の範囲（useCustomRange()）。なければ直近30日
     *
     * @var array{Time, Time}|null
     */
    private ?array $custom = null;

    public function __construct(
        private readonly DisplayService $display,
        private readonly DailyStatModel $daily,
        private readonly EventModel $events,
        private readonly OogiriModel $oogiris,
        private readonly AnswerModel $answers,
        private readonly AiJudgementModel $judgements,
        private readonly InquiryModel $inquiries,
    ) {
    }

    /**
     * 期間の開始日と終了日（日次集計なので「今日」は前日の集計まで：毎日 4:00 に前日分を集計する）
     *
     * @return array{Time, Time}
     */
    public function range(StatsPeriod $period): array
    {
        $latest = Time::now()->subDays(1)->setTime(0, 0);

        return match ($period) {
            StatsPeriod::Today     => [$latest, $latest],
            StatsPeriod::Yesterday => [$latest->subDays(1), $latest->subDays(1)],
            StatsPeriod::Week      => [$latest->subDays(6), $latest],
            StatsPeriod::Month     => [$latest->subDays(29), $latest],
            StatsPeriod::Custom    => $this->custom ?? [$latest->subDays(29), $latest],
        };
    }

    /**
     * 「期間指定」の日付（A-02。YYYY-MM-DD）。集計済みの最新日（前日）より後は前日にし、最長366日にする（監査 2026-10-21）
     */
    public function useCustomRange(string $from, string $to): void
    {
        $latest = Time::now()->subDays(1)->setTime(0, 0);
        $end    = $to !== '' ? Time::parse($to)->setTime(0, 0) : $latest;
        $end    = $end->isAfter($latest) ? $latest : $end;
        $start  = $from !== '' ? Time::parse($from)->setTime(0, 0) : $end->subDays(29);

        if ($start->isAfter($end)) {
            $start = $end;
        }
        if ($start->isBefore($end->subDays(365))) {
            $start = $end->subDays(365);
        }
        $this->custom = [$start, $end];
    }

    /**
     * 期間の集計値（daily_stats を合算）
     *
     * @return Summary
     */
    public function summary(StatsPeriod $period): array
    {
        [$from, $to] = $this->range($period);
        $sum         = $this->sumBetween($from, $to);
        $total       = $this->sumBetween(Time::parse('2000-01-01'), $to);
        $oogiris     = $sum['oogiris'];

        return [
            'pv'                 => $sum['pv'],
            'pvTotal'            => $total['pv'],
            'oogiris'            => $oogiris,
            'oogirisTotal'       => $total['oogiris'],
            'answers'            => $sum['answers'],
            'answersTotal'       => $total['answers'],
            'answersPerOogiri'   => $oogiris === 0 ? 0.0 : $sum['answers'] / $oogiris,
            'shareRecruit'       => $sum['share_recruit'],
            'shareAnswer'        => $sum['share_answer'],
            'shareResult'        => $sum['share_result'],
            'reports'            => $sum['reports'],
            'hidden'             => $sum['hidden'],
            'answerers'          => $sum['answerers'],
            'converted'          => $sum['converted'],
            'conversionRate'     => $sum['answerers'] === 0 ? 0.0 : $sum['converted'] / $sum['answerers'] * 100,
            'derived'            => $sum['derived'],
            'derivedCoefficient' => $oogiris === 0 ? 0.0 : $sum['derived'] / $oogiris,
        ];
    }

    /**
     * 集計対象の日付の範囲。例：「10月3日〜10月9日」
     */
    public function rangeLabel(StatsPeriod $period): string
    {
        [$from, $to] = $this->range($period);

        return $from->toDateString() === $to->toDateString() ? $this->display->date($to) : $this->display->date($from) . '〜' . $this->display->date($to);
    }

    /**
     * 最重要KPI（表示用に整形済み）
     *
     * @return array{conversionRate: string, answerers: string, converted: string, coefficient: string, oogiris: string, derived: string}
     */
    public function kpi(StatsPeriod $period): array
    {
        $s = $this->summary($period);
        $d = $this->display;

        return [
            'conversionRate' => $d->decimal($s['conversionRate'], 1) . '%',
            'answerers'      => $d->number($s['answerers']) . '人',
            'converted'      => $d->number($s['converted']) . '人',
            'coefficient'    => $d->decimal($s['derivedCoefficient'], 2),
            'oogiris'        => $d->number($s['oogiris']),
            'derived'        => $d->number($s['derived']),
        ];
    }

    /**
     * 指標カード（表示用に整形済み）
     *
     * @return list<array{label: string, value: string, sub: string}>
     */
    public function tiles(StatsPeriod $period): array
    {
        $s = $this->summary($period);
        $d = $this->display;

        return [
            ['label' => 'PV', 'value' => $d->number($s['pv']), 'sub' => '累計 ' . $d->number($s['pvTotal'])],
            ['label' => '開催数', 'value' => $d->number($s['oogiris']), 'sub' => '累計 ' . $d->number($s['oogirisTotal'])],
            ['label' => '回答数', 'value' => $d->number($s['answers']), 'sub' => '累計 ' . $d->number($s['answersTotal'])],
            ['label' => '1開催あたり平均回答数', 'value' => $d->decimal($s['answersPerOogiri'], 1), 'sub' => '回答数 ÷ 開催数'],
            ['label' => '募集シェア', 'value' => $d->number($s['shareRecruit']), 'sub' => '開催完了画面・開催者画面から'],
            ['label' => '回答シェア', 'value' => $d->number($s['shareAnswer']), 'sub' => '回答完了画面・一覧から'],
            ['label' => '結果シェア', 'value' => $d->number($s['shareResult']), 'sub' => '結果発表ページから'],
            ['label' => '通報 ／ 非表示', 'value' => $d->number($s['reports']) . ' ／ ' . $d->number($s['hidden']), 'sub' => '非表示は通報・NG・管理者判断の合計'],
        ];
    }

    /**
     * 直近14日の日別開催数のグラフ（古い順）。SVG（viewBox 0 0 140 100）に置く棒の座標まで計算して返す
     *
     * @return array{bars: list<array{label: string, tip: string, x: int, y: int, height: int}>, max: string, mid: string, latest: string}
     */
    public function dailyChart(): array
    {
        $latest = Time::now()->subDays(1)->setTime(0, 0);
        $rows   = [];

        foreach ($this->daily->where('date >=', $latest->subDays(13)->toDateString())->where('date <=', $latest->toDateString())->findAll() as $row) {
            $rows[$row->date->toDateString()] = $row->oogiris;
        }
        $counts = [];

        for ($i = 13; $i >= 0; $i--) {
            $day      = $latest->subDays($i);
            $counts[] = ['date' => $day, 'count' => $rows[$day->toDateString()] ?? 0];
        }
        $max  = max(1, ...array_column($counts, 'count'));
        $bars = [];

        foreach ($counts as $i => $day) {
            $height = (int) round($day['count'] / $max * 100);
            $bars[] = ['label' => $day['date']->format('j'), 'tip' => $this->display->date($day['date']) . '：' . $day['count'] . '開催', 'x' => $i * 10 + 1, 'y' => 100 - $height, 'height' => $height];
        }
        $last = $counts[13];

        return [
            'bars'   => $bars,
            'max'    => $this->display->number($max),
            'mid'    => $this->display->number(intdiv($max, 2)),
            'latest' => $this->display->date($last['date']) . '　' . $this->display->number($last['count']) . ' 開催',
        ];
    }

    /**
     * 「対応が必要なもの」の件数（これは今の値を数える）
     *
     * @return array{moderation: int, ngReview: int, inquiries: int, aiPending: int}
     */
    public function todos(): array
    {
        return [
            'moderation' => $this->judgements->where('resolved', 0)->countAllResults(),
            'ngReview'   => $this->judgements->where('resolved', 0)->where('ng_detail IS NOT NULL')->countAllResults(),
            'inquiries'  => $this->inquiries->where('status', 'todo')->countAllResults(),
            'aiPending'  => $this->judgements->where('resolved', 0)->where('queued', 1)->countAllResults(),
        ];
    }

    /**
     * 左メニューのバッジ（未処理件数）
     *
     * @return array{todo: int, inquiries: int}
     */
    public function badges(): array
    {
        $todos = $this->todos();

        return ['todo' => $todos['moderation'], 'inquiries' => $todos['inquiries']];
    }

    /**
     * 1日分を集計して daily_stats に入れる（S-06、cron。既にあれば上書き）。PV のカウンタにも反映する
     *
     * @return array<string, int> 集計した値
     */
    public function aggregateDay(Time $day): array
    {
        $from = $day->setTime(0, 0)->toDateTimeString();
        $to   = $day->setTime(23, 59, 59)->toDateTimeString();
        $n    = fn (EventName $name) => $this->events->where('name', $name->value)->where('created_at >=', $from)->where('created_at <=', $to)->countAllResults();

        // 回答者 → 開催者 転換：その日に回答したブラウザのうち、その後（同日〜当日まで）に開催したブラウザ
        $answerers = $this->events->builder()->select('browser_id_hash')->distinct()
            ->where('name', EventName::Answer->value)->where('created_at >=', $from)->where('created_at <=', $to)->where('browser_id_hash IS NOT NULL')
            ->get()->getResultArray();
        $hashes    = array_column($answerers, 'browser_id_hash');
        $converted = $hashes === [] ? 0 : (int) $this->events->builder()->select('COUNT(DISTINCT browser_id_hash) AS n')
            ->where('name', EventName::Create->value)->where('created_at >=', $from)->whereIn('browser_id_hash', $hashes)
            ->get()->getRowArray()['n'];

        $row = [
            'date'          => $day->toDateString(),
            'pv'            => $n(EventName::PageView) + $n(EventName::Visit),
            'oogiris'       => $this->oogiris->where('created_at >=', $from)->where('created_at <=', $to)->countAllResults(),
            'answers'       => $this->answers->where('created_at >=', $from)->where('created_at <=', $to)->countAllResults(),
            'share_recruit' => $n(EventName::ShareRecruit),
            'share_answer'  => $n(EventName::ShareAnswer),
            'share_result'  => $n(EventName::ShareResult),
            'reports'       => (new ReportModel())->where('created_at >=', $from)->where('created_at <=', $to)->countAllResults(),
            'hidden'        => $this->answers->where('updated_at >=', $from)->where('updated_at <=', $to)->where('status', AnswerStatus::Hidden->value)->countAllResults()
                + $this->oogiris->where('updated_at >=', $from)->where('updated_at <=', $to)->where('status', OogiriStatus::Hidden->value)->countAllResults(),
            'answerers' => count($hashes),
            'converted' => $converted,
            'derived'   => $this->oogiris->where('created_at >=', $from)->where('created_at <=', $to)->where('parent_oogiri_id IS NOT NULL')->countAllResults(),
        ];
        $existing = $this->daily->where('date', $row['date'])->first();
        $existing === null ? $this->daily->insert($row) : $this->daily->update($existing->id, $row);

        // 同じ日を2回集計したときは、大喜利ごとの PV を足さない（二重に増えるため。監査 2026-10-21）
        if ($existing !== null) {
            return $row;
        }

        // PV カウンタ（大喜利ごと）に反映：その日の募集URL流入と PV を加算
        $perOogiri = $this->events->builder()->select('oogiri_id, COUNT(*) AS n')
            ->whereIn('name', [EventName::PageView->value, EventName::Visit->value])->where('created_at >=', $from)->where('created_at <=', $to)->where('oogiri_id IS NOT NULL')
            ->groupBy('oogiri_id')->get()->getResultArray();

        foreach ($perOogiri as $p) {
            $this->oogiris->increment((int) $p['oogiri_id'], 'pv', (int) $p['n']);
        }

        return $row;
    }

    /**
     * 集計済みの古い生ログを消す（events の肥大化対策：SPEC 4.1「ログ集約→cronで集計」。stats:daily の後に呼ぶ）
     *
     * @return int 消した件数
     */
    public function pruneEvents(?Time $now = null): int
    {
        $before = ($now ?? Time::now())->subDays(self::EVENT_KEEP_DAYS)->toDateTimeString();
        $this->events->where('created_at <', $before)->delete();

        return $this->events->db->affectedRows();
    }

    /**
     * @return array{pv: int, oogiris: int, answers: int, share_recruit: int, share_answer: int, share_result: int, reports: int, hidden: int, answerers: int, converted: int, derived: int}
     */
    private function sumBetween(Time $from, Time $to): array
    {
        $cols = ['pv', 'oogiris', 'answers', 'share_recruit', 'share_answer', 'share_result', 'reports', 'hidden', 'answerers', 'converted', 'derived'];
        $row  = $this->daily->builder()->select(implode(', ', array_map(static fn (string $c) => "COALESCE(SUM({$c}), 0) AS {$c}", $cols)))
            ->where('date >=', $from->toDateString())->where('date <=', $to->toDateString())->get()->getRowArray() ?? [];
        $sum = [];

        foreach ($cols as $c) {
            $sum[$c] = (int) ($row[$c] ?? 0);
        }

        return $sum;
    }
}
