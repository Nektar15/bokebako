<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Enums\StatsPeriod;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-02）用のダミーデータ：集計値
 *
 * TODO(inner): 工程4で Service が集計テーブル（S-06）を読むようになったら削除する。
 *
 * @phpstan-type Summary array{
 *                       pv: int, pvTotal: int, oogiris: int, oogirisTotal: int, answers: int, answersTotal: int, answersPerOogiri: float,
 *                       shareRecruit: int, shareAnswer: int, shareResult: int, reports: int, hidden: int,
 *                       answerers: int, converted: int, conversionRate: float, derived: int, derivedCoefficient: float
 *                       }
 */
final class StatsFixture
{
    /**
     * 期間の集計値（見本の数字を期間の日数で伸縮させたもの）
     *
     * @return Summary
     */
    public static function summary(StatsPeriod $period): array
    {
        $factor = match ($period) {
            StatsPeriod::Today     => 1.0,
            StatsPeriod::Yesterday => 0.94,
            StatsPeriod::Week      => 7.1,
            StatsPeriod::Month     => 30.6,
            StatsPeriod::Custom    => 30.6,
        };
        $rate = match ($period) {
            StatsPeriod::Today     => 6.4,
            StatsPeriod::Yesterday => 6.1,
            StatsPeriod::Week      => 6.2,
            StatsPeriod::Month     => 5.9,
            StatsPeriod::Custom    => 5.9,
        };
        $coefficient = match ($period) {
            StatsPeriod::Today     => 1.42,
            StatsPeriod::Yesterday => 1.38,
            StatsPeriod::Week      => 1.50,
            StatsPeriod::Month     => 1.47,
            StatsPeriod::Custom    => 1.47,
        };
        $oogiris   = (int) round(214 * $factor);
        $answerers = (int) round(3120 * $factor);

        return [
            'pv'                 => (int) round(32410 * $factor),
            'pvTotal'            => 1284550,
            'oogiris'            => $oogiris,
            'oogirisTotal'       => 12480,
            'answers'            => (int) round(6980 * $factor),
            'answersTotal'       => 402118,
            'answersPerOogiri'   => 32.6,
            'shareRecruit'       => (int) round(188 * $factor),
            'shareAnswer'        => (int) round(1412 * $factor),
            'shareResult'        => (int) round(96 * $factor),
            'reports'            => (int) round(18 * $factor),
            'hidden'             => (int) round(4 * $factor),
            'answerers'          => $answerers,
            'converted'          => (int) round($answerers * $rate / 100),
            'conversionRate'     => $rate,
            'derived'            => (int) round($oogiris * $coefficient),
            'derivedCoefficient' => $coefficient,
        ];
    }

    /**
     * 直近14日の日別開催数（古い順）
     *
     * @return list<array{date: Time, count: int}>
     */
    public static function dailyOogiris(): array
    {
        $counts = [148, 162, 155, 171, 190, 238, 226, 181, 176, 199, 204, 263, 248, 214];
        $today  = Time::now()->setTime(0, 0);
        $days   = [];

        foreach ($counts as $i => $count) {
            $days[] = ['date' => $today->subDays(13 - $i), 'count' => $count];
        }

        return $days;
    }

    /**
     * 「対応が必要なもの」の件数
     *
     * @return array{moderation: int, ngReview: int, inquiries: int, aiPending: int}
     */
    public static function todos(): array
    {
        return ['moderation' => 6, 'ngReview' => 3, 'inquiries' => 2, 'aiPending' => 1];
    }
}
