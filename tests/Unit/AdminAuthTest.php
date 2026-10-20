<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Filters\AdminAuth;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * 仮の管理者認証（CODING_RULES 7-1）
 *
 * production では常に拒否されること、判定に迷う場合も拒否されることを保証する。
 * A-01 実装後、この仮認証を削除するときにこのテストも置き換える。
 *
 * @internal
 */
final class AdminAuthTest extends CIUnitTestCase
{
    public function testDevelopmentAllowsAnyIp(): void
    {
        $this->assertTrue(AdminAuth::allowsTemporaryAdmin('203.0.113.9', 'development', ''));
    }

    public function testStagingAllowsOnlyListedIp(): void
    {
        $this->assertTrue(AdminAuth::allowsTemporaryAdmin('203.0.113.9', 'staging', '203.0.113.9, 198.51.100.1'));
        $this->assertFalse(AdminAuth::allowsTemporaryAdmin('203.0.113.10', 'staging', '203.0.113.9'));
    }

    public function testStagingWithoutListRejects(): void
    {
        $this->assertFalse(AdminAuth::allowsTemporaryAdmin('203.0.113.9', 'staging', ''));
    }

    public function testProductionAlwaysRejects(): void
    {
        $this->assertFalse(AdminAuth::allowsTemporaryAdmin('127.0.0.1', 'production', '127.0.0.1'));
    }

    public function testUnknownEnvironmentRejects(): void
    {
        $this->assertFalse(AdminAuth::allowsTemporaryAdmin('127.0.0.1', '', '127.0.0.1'));
        $this->assertFalse(AdminAuth::allowsTemporaryAdmin('127.0.0.1', 'prod', '127.0.0.1'));
    }
}
