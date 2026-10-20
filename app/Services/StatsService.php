<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatsPeriod;
use App\Services\Fixtures\StatsFixture;
use CodeIgniter\I18n\Time;

/**
 * ダッシュボード（A-02）の集計値と、管理画面共通の未処理件数
 *
 * TODO(inner): 集計テーブル（S-06 の日次集計先）から読む。生ログは直接読まない（SPEC 9）。
 *
 * @phpstan-import-type Summary from StatsFixture
 */
final class StatsService
{
    public function __construct(private readonly DisplayService $display)
    {
    }

    /**
     * 期間の集計値
     *
     * @return Summary
     */
    public function summary(StatsPeriod $period): array
    {
        // TODO(inner): 集計テーブルから期間で合算する
        return StatsFixture::summary($period);
    }

    /**
     * 集計対象の日付の範囲。例：「10月3日〜10月9日」
     */
    public function rangeLabel(StatsPeriod $period): string
    {
        // 日次集計なので「今日」は前日の集計まで（毎日 4:00 に前日分を集計する）
        $latest = Time::now()->subDays(1);

        return match ($period) {
            StatsPeriod::Today                      => $this->display->date($latest),
            StatsPeriod::Yesterday                  => $this->display->date($latest->subDays(1)),
            StatsPeriod::Week                       => $this->display->date($latest->subDays(6)) . '〜' . $this->display->date($latest),
            StatsPeriod::Month, StatsPeriod::Custom => $this->display->date($latest->subDays(29)) . '〜' . $this->display->date($latest),
        };
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
     * 直近14日の日別開催数のグラフ（古い順）
     *
     * SVG（viewBox 0 0 140 100）に置く棒の座標まで計算して返す。View では計算しない。
     *
     * @return array{bars: list<array{label: string, tip: string, x: int, y: int, height: int}>, max: string, mid: string, latest: string}
     */
    public function dailyChart(): array
    {
        // TODO(inner): 集計テーブルから日別に取る
        $daily = StatsFixture::dailyOogiris();
        $max   = max(1, ...array_column($daily, 'count'));
        $bars  = [];

        foreach ($daily as $i => $day) {
            $height = (int) round($day['count'] / $max * 100);
            $bars[] = [
                'label'  => $day['date']->format('j'),
                'tip'    => $this->display->date($day['date']) . '：' . $day['count'] . '開催',
                'x'      => $i * 10 + 1,
                'y'      => 100 - $height,
                'height' => $height,
            ];
        }
        $last = $daily[count($daily) - 1];

        return [
            'bars'   => $bars,
            'max'    => $this->display->number($max),
            'mid'    => $this->display->number(intdiv($max, 2)),
            'latest' => $this->display->date($last['date']) . '　' . $this->display->number($last['count']) . ' 開催',
        ];
    }

    /**
     * 「対応が必要なもの」の件数
     *
     * @return array{moderation: int, ngReview: int, inquiries: int, aiPending: int}
     */
    public function todos(): array
    {
        // TODO(inner): reports・ng 検知・inquiries・AI判定キューをそれぞれ数える
        return StatsFixture::todos();
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
}
