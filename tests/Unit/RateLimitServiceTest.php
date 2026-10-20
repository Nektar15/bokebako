<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Filters\BrowserId;
use App\Services\RateLimitService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Config\Services;
use Tests\Support\DatabaseTestCase;

/**
 * 連続投稿の制限（burst_count 回 / burst_window_sec 秒、制限方法、投稿停止）。CODING_RULES 13章
 *
 * @internal
 */
final class RateLimitServiceTest extends DatabaseTestCase
{
    private ?string $remoteAddr = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->remoteAddr === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $this->remoteAddr;
        }
        parent::tearDown();
    }

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

    /**
     * Cookie（ブラウザID）を変えても、同じ IP からは IP 単位の上限で止まる（レビュー指摘 3）
     */
    public function testChangingBrowserCookieHitsIpLimit(): void
    {
        $limit   = service('rateLimit');
        $perIp   = 3 * RateLimitService::IP_FACTOR; // 既定の burst_count は 3
        $allowed = 0;

        for ($browser = 0; $browser < RateLimitService::IP_FACTOR + 1; $browser++) {
            $this->actAs('browser-' . $browser, '198.51.100.7');

            for ($i = 0; $i < 3; $i++) {
                try {
                    $limit->assertAllowed(RateLimitService::ACTION_ANSWER);
                    $allowed++;
                } catch (AppException) {
                }
            }
        }
        $this->assertSame($perIp, $allowed);
        // 別の IP なら通る（IP を共有していない人は巻き込まない）
        $this->actAs('browser-x', '198.51.100.8');
        $limit->assertAllowed(RateLimitService::ACTION_ANSWER);
    }

    /**
     * 合言葉は IP を変えて総当たりされても、大喜利ごとの上限で止まる
     */
    public function testPassphraseIsLimitedPerTargetAcrossIps(): void
    {
        $limit   = service('rateLimit');
        $allowed = 0;

        for ($n = 0; $allowed < RateLimitService::PASSPHRASE_TARGET_ATTEMPTS + 5 && $n < 50; $n++) {
            $this->actAs('', '192.0.2.' . $n);

            try {
                $limit->assertAllowed(RateLimitService::ACTION_PASSPHRASE, 'k7Qm2xPz');
                $allowed++;
            } catch (AppException) {
                break;
            }
        }
        $this->assertSame(RateLimitService::PASSPHRASE_TARGET_ATTEMPTS, $allowed);
        // 別の大喜利は別に数える
        $limit->assertAllowed(RateLimitService::ACTION_PASSPHRASE, 'Zp3cW9aQ');
    }

    /**
     * 現在のリクエストのブラウザID（Cookie）と IP を差し替える
     */
    private function actAs(string $browserId, string $ip): void
    {
        $_COOKIE[BrowserId::COOKIE_NAME] = $browserId;
        $_SERVER['REMOTE_ADDR']          = $ip;
        Services::injectMock('request', new IncomingRequest(new App(), new SiteURI(new App(), 'o/x'), null, new UserAgent()));
    }
}
