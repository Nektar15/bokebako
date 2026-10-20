<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;

/**
 * S-06 日次集計：生ログ（events）を日ごとに集計して daily_stats に入れ、PV のカウンタにも反映する
 *
 * 毎日 4:00 に前日分を実行する（README）。日付を渡せばその日を集計し直せる：php spark stats:daily 2026-10-19
 */
final class DailyStats extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'stats:daily';
    protected $description = 'S-06 日次集計：前日（または指定日）の計測イベントを集計する';
    protected $usage       = 'stats:daily [YYYY-MM-DD]';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        $day = isset($params[0]) ? Time::parse($params[0]) : Time::now()->subDays(1);
        $row = service('stats')->aggregateDay($day);
        CLI::write("stats:daily: {$row['date']} を集計しました（PV {$row['pv']}、開催 {$row['oogiris']}、回答 {$row['answers']}）");
    }
}
