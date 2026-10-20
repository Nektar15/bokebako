<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\DevSeeder;
use App\Enums\AdminRole;
use App\Services\AdminAuthService;
use Tests\Support\DatabaseTestCase;

/**
 * A-01 ログイン・ログアウトと、管理画面の入口（未ログインはログイン画面へ、IP 制限）
 *
 * @internal
 */
final class AdminLoginTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        putenv('ADMIN_ALLOWED_IPS');
        unset($_ENV['ADMIN_ALLOWED_IPS'], $_SERVER['ADMIN_ALLOWED_IPS']);
        $_SESSION = [];
        parent::tearDown();
    }

    public function testAdminPagesRequireLogin(): void
    {
        foreach (['admin', 'admin/oogiri', 'admin/settings', 'admin/accounts'] as $path) {
            $this->get($path)->assertRedirectTo(site_url('admin/login'));
        }
        $this->post('admin/oogiri/12480/hide')->assertRedirectTo(site_url('admin/login'));
        $this->seeInDatabase('oogiris', ['id' => 12480, 'status' => 'open']);
    }

    public function testLoginPageIsShown(): void
    {
        $result = $this->get('admin/login');
        $result->assertOK();
        $result->assertSee('管理画面にログイン');
        $result->assertDontSee('ダッシュボード'); // 左メニューは出さない
    }

    public function testLoginWithCorrectPassword(): void
    {
        $result = $this->post('admin/login', ['login_id' => 'dev-admin', 'password' => DevSeeder::ADMIN_PASSWORD]);
        $result->assertRedirectTo(site_url('admin'));
        $this->assertSame(1, $_SESSION[AdminAuthService::SESSION_ID] ?? null);
        $this->seeInDatabase('admin_logs', ['target_type' => 'admin', 'target_id' => 1, 'what' => 'ログイン']);
        $this->assertNotNull(model('AdminModel')->find(1)?->last_login_at);
    }

    public function testLoginWithWrongPasswordShowsError(): void
    {
        $result = $this->post('admin/login', ['login_id' => 'dev-admin', 'password' => 'wrong']);
        $result->assertRedirectTo(site_url('admin/login'));
        $result->assertSessionHas('errors', ['login' => 'ID またはパスワードが違います']);
        $this->assertArrayNotHasKey(AdminAuthService::SESSION_ID, $_SESSION);
    }

    public function testLogoutEndsSession(): void
    {
        $result = $this->asAdmin()->post('admin/logout');
        $result->assertRedirectTo(site_url('admin/login'));
        $this->assertArrayNotHasKey(AdminAuthService::SESSION_ID, $_SESSION);
        $this->seeInDatabase('admin_logs', ['target_type' => 'admin', 'what' => 'ログアウト']);
    }

    public function testLoggedInPagesAreNotCached(): void
    {
        $result = $this->asAdmin()->get('admin');
        $result->assertOK();
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
        $result->assertSee('ログアウト');
    }

    public function testIpRestrictionAppliesToLoginPageToo(): void
    {
        putenv('ADMIN_ALLOWED_IPS=198.51.100.1');
        $_ENV['ADMIN_ALLOWED_IPS'] = '198.51.100.1';
        $this->get('admin/login')->assertStatus(403);
        $this->asAdmin(AdminRole::Super)->get('admin')->assertStatus(403);
    }
}
