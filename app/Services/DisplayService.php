<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminLog;
use CodeIgniter\I18n\Time;

/**
 * 画面表示用の整形。View では計算しない（CODING_RULES 8-2）ので、ここで文字列にしてから渡す。
 */
final class DisplayService
{
    /**
     * 例：「9月17日 22:15」
     */
    public function datetime(Time $time): string
    {
        return $time->format('n月j日 H:i');
    }

    /**
     * 例：「9月16日 22:15〜9月17日 22:15」
     */
    public function period(Time $from, Time $to): string
    {
        return $this->datetime($from) . '〜' . $this->datetime($to);
    }

    /**
     * 残り時間。例：「18時間32分」「12分」。過ぎていれば「0分」
     */
    public function remaining(Time $until, ?Time $now = null): string
    {
        $seconds = $until->getTimestamp() - ($now ?? Time::now())->getTimestamp();
        if ($seconds <= 0) {
            return '0分';
        }
        $hours   = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return ($hours > 0 ? $hours . '時間' : '') . $minutes . '分';
    }

    /**
     * 残り時間バーの幅（0〜100）。残りが多いほど大きい
     */
    public function progressPercent(Time $from, Time $to, ?Time $now = null): int
    {
        $total = max(1, $to->getTimestamp() - $from->getTimestamp());
        $left  = $to->getTimestamp() - ($now ?? Time::now())->getTimestamp();

        return (int) max(0, min(100, round($left / $total * 100)));
    }

    /**
     * 相対表示。例：「たった今」「5分前」「3時間前」「2日前」
     */
    public function ago(Time $time, ?Time $now = null): string
    {
        $seconds = ($now ?? Time::now())->getTimestamp() - $time->getTimestamp();
        if ($seconds < 60) {
            return 'たった今';
        }
        if ($seconds < 3600) {
            return intdiv($seconds, 60) . '分前';
        }
        if ($seconds < 86400) {
            return intdiv($seconds, 3600) . '時間前';
        }

        return intdiv($seconds, 86400) . '日前';
    }

    public function number(int $n): string
    {
        return number_format($n);
    }

    /**
     * 例：「10月9日」（ダッシュボードの期間表示）
     */
    public function date(Time $time): string
    {
        return $time->format('n月j日');
    }

    /**
     * 例：「2026年10月9日 08:12」（管理画面の詳細）
     */
    public function full(Time $time): string
    {
        return $time->format('Y年n月j日 H:i');
    }

    /**
     * 例：「10/09 21:14」（管理画面の一覧。幅を揃えるため 0 埋め）
     */
    public function short(Time $time): string
    {
        return $time->format('m/d H:i');
    }

    /**
     * 例：「18:02」（同じ日の中での時刻）
     */
    public function time(Time $time): string
    {
        return $time->format('H:i');
    }

    /**
     * <input type="datetime-local"> の value の形。例：「2026-10-09T21:14」
     */
    public function inputDatetime(Time $time): string
    {
        return $time->format('Y-m-d\TH:i');
    }

    /**
     * 例：「10/09」（管理画面の一覧）
     */
    public function shortDate(Time $time): string
    {
        return $time->format('m/d');
    }

    /**
     * 例：「0.80%」。分母が 0 なら「—」（CTR など）
     */
    public function percent(int $numerator, int $denominator, int $decimals = 2): string
    {
        if ($denominator === 0) {
            return '—';
        }

        return number_format($numerator / $denominator * 100, $decimals) . '%';
    }

    /**
     * 例：「1.50」「6.2」
     */
    public function decimal(float $n, int $decimals): string
    {
        return number_format($n, $decimals);
    }

    /**
     * 操作履歴を partials/admin_history 用に整形する。例：when「10/09 18:41・自動」
     *
     * @param list<AdminLog> $logs
     *
     * @return list<array{when: string, what: string}>
     */
    public function logs(array $logs): array
    {
        return array_map(fn (AdminLog $log) => ['when' => $this->short($log->created_at) . '・' . $log->actor, 'what' => $log->what], $logs);
    }

    /**
     * ページ送りを partials/admin_pager 用に整形する
     *
     * 表示するページ：先頭・末尾・現在の前後1つ。飛ぶところは null（「…」）
     *
     * @param array{total: int, page: int, perPage: int, pages: int} $page
     * @param string                                                 $baseUrl page 以外のクエリを含んだ URL（例：/admin/oogiri?status=open）
     *
     * @return array{label: string, current: int, links: list<array{page: int, url: string}|null>}
     */
    public function pager(array $page, string $baseUrl): array
    {
        $from  = $page['total'] === 0 ? 0 : ($page['page'] - 1) * $page['perPage'] + 1;
        $to    = min($page['total'], $page['page'] * $page['perPage']);
        $label = $page['total'] === 0 ? '0件' : "{$from}–{$to}件 ／ 全" . number_format($page['total']) . '件';
        $glue  = str_contains($baseUrl, '?') ? '&' : '?';

        $links = [];
        $last  = 0;

        for ($n = 1; $n <= $page['pages']; $n++) {
            if ($n !== 1 && $n !== $page['pages'] && abs($n - $page['page']) > 1) {
                continue;
            }
            if ($n - $last > 1) {
                $links[] = null;
            }
            $links[] = ['page' => $n, 'url' => $baseUrl . $glue . 'page=' . $n];
            $last    = $n;
        }

        return ['label' => $label, 'current' => $page['page'], 'links' => $links];
    }
}
