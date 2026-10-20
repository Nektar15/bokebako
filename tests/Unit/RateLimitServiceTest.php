<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Services\RateLimitService;
use Tests\Support\DatabaseTestCase;

/**
 * 連続投稿の制限（burst_count 回 / burst_window_sec 秒、制限方法、投稿停止）。CODING_RULES 13章
 *
 * @internal
 */
final class RateLimitServiceTest extends DatabaseTestCase
{
    public function testAllowsUpToBurstCountThenRejects(): void
    {
        $limit = service('rateLimit');

        for ($i = 0; $i < 3; $i++) {
            $limit->assertAllowed(RateLimitService::ACTION_ANSWER);
        }

        try {
            $limit->assertAllowed(RateLimitService::ACTION_ANSWER);
            $this->fail('4回目は通らないはず');
        } catch (AppException $e) {
            $this->assertSame(ErrorCode::RateLimited, $e->errorCode);
        }
        // 既定の制限方法「一時的」では投稿停止は記録しない
        $this->assertSame(0, $this->db->table('restrictions')->countAll());
    }

    public function testActionsAreCountedSeparately(): void
    {
        $limit = service('rateLimit');

        for ($i = 0; $i < 3; $i++) {
            $limit->assertAllowed(RateLimitService::ACTION_ANSWER);
        }
        $limit->assertAllowed(RateLimitService::ACTION_CREATE);
        $this->assertTrue(true);
    }

    public function testBanMethodRecordsRestriction(): void
    {
        $this->db->table('settings')->where('name', 'limit_method')->delete();
        $this->db->table('settings')->insert(['name' => 'limit_method', 'value' => 'ban', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
        service('settings')->clearCache();
        $limit = service('rateLimit');

        for ($i = 0; $i < 3; $i++) {
            $limit->assertAllowed(RateLimitService::ACTION_ANSWER);
        }

        try {
            $limit->assertAllowed(RateLimitService::ACTION_ANSWER);
        } catch (AppException) {
        }
        $this->assertSame(1, $this->db->table('restrictions')->countAll());
        $this->assertTrue($limit->isRestricted());
        // 投稿停止中は別の操作も通らない
        $this->expectException(AppException::class);
        $limit->assertAllowed(RateLimitService::ACTION_CREATE);
    }

    public function testExpiredRestrictionIsIgnored(): void
    {
        $limit = service('rateLimit');
        $limit->restrict(-10, '期限切れ');
        $this->assertFalse($limit->isRestricted());
        $limit->restrict(600, '有効');
        $this->assertTrue($limit->isRestricted());
    }

    public function testBurstingIsDetectedBeyondCount(): void
    {
        $limit = service('rateLimit');
        $this->assertFalse($limit->isBursting(RateLimitService::ACTION_CREATE));
        $this->assertFalse($limit->isBursting(RateLimitService::ACTION_CREATE));
        $this->assertFalse($limit->isBursting(RateLimitService::ACTION_CREATE));
        $this->assertTrue($limit->isBursting(RateLimitService::ACTION_CREATE));
    }
}
