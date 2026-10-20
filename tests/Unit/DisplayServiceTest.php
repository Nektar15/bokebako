<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DisplayService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;

/** 画面表示用の整形 */
final class DisplayServiceTest extends CIUnitTestCase
{
    public function testRemaining(): void
    {
        $d   = new DisplayService();
        $now = Time::parse('2026-09-17 03:43:00');
        $this->assertSame('18時間32分', $d->remaining(Time::parse('2026-09-17 22:15:00'), $now));
        $this->assertSame('12分', $d->remaining(Time::parse('2026-09-17 03:55:30'), $now));
        $this->assertSame('0分', $d->remaining(Time::parse('2026-09-17 03:00:00'), $now));
    }

    public function testProgressPercent(): void
    {
        $d = new DisplayService();
        $from = Time::parse('2026-09-16 22:15:00');
        $to   = Time::parse('2026-09-17 22:15:00');
        $this->assertSame(100, $d->progressPercent($from, $to, $from));
        $this->assertSame(50, $d->progressPercent($from, $to, Time::parse('2026-09-17 10:15:00')));
        $this->assertSame(0, $d->progressPercent($from, $to, Time::parse('2026-09-18 00:00:00')));
    }

    public function testAgo(): void
    {
        $d   = new DisplayService();
        $now = Time::parse('2026-09-17 12:00:00');
        $this->assertSame('たった今', $d->ago(Time::parse('2026-09-17 11:59:30'), $now));
        $this->assertSame('5分前', $d->ago(Time::parse('2026-09-17 11:55:00'), $now));
        $this->assertSame('3時間前', $d->ago(Time::parse('2026-09-17 09:00:00'), $now));
        $this->assertSame('2日前', $d->ago(Time::parse('2026-09-15 12:00:00'), $now));
    }

    public function testDatetime(): void
    {
        $this->assertSame('9月17日 22:15', (new DisplayService())->datetime(Time::parse('2026-09-17 22:15:00')));
    }
}
