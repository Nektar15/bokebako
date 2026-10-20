<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\I18n\Time;

/**
 * 画面表示用の整形。View では計算しない（CODING_RULES 8-2）ので、ここで文字列にしてから渡す。
 */
final class DisplayService
{
    /** 例：「9月17日 22:15」 */
    public function datetime(Time $time): string
    {
        return $time->format('n月j日 H:i');
    }

    /** 例：「9月16日 22:15〜9月17日 22:15」 */
    public function period(Time $from, Time $to): string
    {
        return $this->datetime($from) . '〜' . $this->datetime($to);
    }

    /** 残り時間。例：「18時間32分」「12分」。過ぎていれば「0分」 */
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

    /** 残り時間バーの幅（0〜100）。残りが多いほど大きい */
    public function progressPercent(Time $from, Time $to, ?Time $now = null): int
    {
        $total = max(1, $to->getTimestamp() - $from->getTimestamp());
        $left  = $to->getTimestamp() - ($now ?? Time::now())->getTimestamp();

        return (int) max(0, min(100, round($left / $total * 100)));
    }

    /** 相対表示。例：「たった今」「5分前」「3時間前」「2日前」 */
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
}
