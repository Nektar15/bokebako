<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Database\Seeds\DevSeeder;
use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Services\AdminAuthService;
use CodeIgniter\I18n\Time;
use Tests\Support\DatabaseTestCase;

/**
 * A-01 管理者の認証：パスワード照合、試行回数制限と一時ロック、セッションの期限、IP 制限
 *
 * @internal
 */
final class AdminAuthServiceTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        Time::setTestNow();
        $_SESSION = [];
        parent::tearDown();
    }

    public function testCorrectPasswordReturnsAccountAndResetsFailures(): void
    {
        $this->db->table('admins')->where('login_id', 'dev-admin')->update(['failed_count' => 3]);
        $account = service('adminAuth')->verifyPassword('dev-admin', DevSeeder::ADMIN_PASSWORD);
        $this->assertSame('dev-admin', $account->login_id);
        $this->seeInDatabase('admins', ['login_id' => 'dev-admin', 'failed_count' => 0]);
    }

    public function testWrongPasswordAndUnknownIdGiveTheSameMessage(): void
    {
        $messages = [];

        foreach ([['dev-admin', 'wrong-password'], ['no-such-admin', DevSeeder::ADMIN_PASSWORD]] as [$id, $password]) {
            try {
                service('adminAuth')->verifyPassword($id, $password);
                $this->fail('通らないはず');
            } catch (ValidationException $e) {
                $messages[] = $e->errors['login'];
            }
        }
        // ID があるかどうかを言い分けない
        $this->assertSame($messages[0], $messages[1]);
        $this->seeInDatabase('admins', ['login_id' => 'dev-admin', 'failed_count' => 1]);
    }

    public function testAccountIsLockedAfterMaxFailures(): void
    {
        for ($i = 0; $i < AdminAuthService::MAX_FAILURES; $i++) {
            try {
                service('adminAuth')->verifyPassword('dev-admin', 'wrong-password');
            } catch (ValidationException) {
            }
        }

        // ロック中は正しいパスワードでも入れない
        try {
            service('adminAuth')->verifyPassword('dev-admin', DevSeeder::ADMIN_PASSWORD);
            $this->fail('ロック中は通らないはず');
        } catch (AppException $e) {
            $this->assertSame(ErrorCode::RateLimited, $e->errorCode);
        }
        $this->seeInDatabase('admin_logs', ['target_type' => 'admin', 'what' => 'ログインの失敗が続いたため一時ロック']);

        // ロックが切れたら入れる
        Time::setTestNow(Time::now()->addMinutes(AdminAuthService::LOCK_MINUTES + 1));
        $this->assertSame('dev-admin', service('adminAuth')->verifyPassword('dev-admin', DevSeeder::ADMIN_PASSWORD)->login_id);
    }

    public function testSameIpIsLimitedAcrossIds(): void
    {
        for ($i = 0; $i < AdminAuthService::IP_ATTEMPTS; $i++) {
            try {
                service('adminAuth')->verifyPassword('guess-' . $i, 'x');
            } catch (ValidationException) {
            }
        }
        $this->expectException(AppException::class);
        service('adminAuth')->verifyPassword('dev-admin', DevSeeder::ADMIN_PASSWORD);
    }

    public function testSessionExpiresAfterIdleTime(): void
    {
        $auth = service('adminAuth');
        $auth->startSession($auth->verifyPassword('dev-admin', DevSeeder::ADMIN_PASSWORD));
        $this->assertNotNull($auth->current());

        Time::setTestNow(Time::now()->addMinutes(AdminAuthService::IDLE_MINUTES + 1));
        $this->assertNull($auth->current());
        $this->assertNull(session(AdminAuthService::SESSION_ID));
    }

    public function testSessionEndsAfterMaxHoursEvenWhenActive(): void
    {
        $auth = service('adminAuth');
        $auth->startSession($auth->verifyPassword('dev-admin', DevSeeder::ADMIN_PASSWORD));

        // 1時間おきに操作し続けても、最長時間を過ぎたらログインし直し
        for ($h = 1; $h <= AdminAuthService::MAX_HOURS; $h++) {
            Time::setTestNow(Time::now()->addHours(1));
            $this->assertNotNull($auth->current());
        }
        Time::setTestNow(Time::now()->addHours(1));
        $this->assertNull($auth->current());
    }

    public function testPasswordChangeEndsOtherSessions(): void
    {
        $auth = service('adminAuth');
        $auth->startSession($auth->verifyPassword('dev-admin', DevSeeder::ADMIN_PASSWORD));
        $this->db->table('admins')->where('login_id', 'dev-admin')->update(['password_hash' => password_hash('another-password-123', PASSWORD_BCRYPT)]);
        $this->assertNull($auth->current());
    }

    public function testDeletedAdminIsLoggedOut(): void
    {
        $auth = service('adminAuth');
        $auth->startSession($auth->verifyPassword('dev-general', DevSeeder::ADMIN_PASSWORD));
        $this->db->table('admins')->where('login_id', 'dev-general')->delete();
        $this->assertNull($auth->current());
    }

    public function testIpRestrictionIsOptional(): void
    {
        $this->assertTrue(AdminAuthService::allowsIp('203.0.113.9', ''));
        $this->assertTrue(AdminAuthService::allowsIp('203.0.113.9', '203.0.113.9, 198.51.100.1'));
        $this->assertFalse(AdminAuthService::allowsIp('203.0.113.10', '203.0.113.9'));
        $this->assertFalse(AdminAuthService::allowsIp('203.0.113.10', ' , 203.0.113.9 '));
    }
}
