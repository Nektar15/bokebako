<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Database\Seeds\DevSeeder;
use App\Entities\AdminUser;
use App\Enums\AdminRole;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use Tests\Support\DatabaseTestCase;

/**
 * A-01 管理者アカウントの追加・権限設定・パスワード（上位管理者だけ。自分と最後の上位管理者は守る）
 *
 * @internal
 */
final class AdminAccountServiceTest extends DatabaseTestCase
{
    private function actAs(int $id, AdminRole $role): void
    {
        service('currentAdmin')->set(new AdminUser($id, 'test', $role));
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testSuperAdminCanAddAdmin(): void
    {
        $this->actAs(1, AdminRole::Super);
        $account = service('adminAccount')->create(['login_id' => 'sakai@example.com', 'name' => '坂井', 'role' => 'general', 'password' => 'long-enough-pass']);
        $this->assertSame(AdminRole::General, $account->role());
        $this->assertTrue(password_verify('long-enough-pass', $account->password_hash));
        $this->seeInDatabase('admin_logs', ['target_type' => 'admin', 'target_id' => $account->id]);
    }

    public function testGeneralAdminCannotManageAccounts(): void
    {
        $this->actAs(2, AdminRole::General);
        $this->expectException(ForbiddenException::class);
        service('adminAccount')->create(['login_id' => 'x-admin', 'name' => 'x', 'role' => 'super', 'password' => 'long-enough-pass']);
    }

    public function testInvalidInputIsRejected(): void
    {
        $this->actAs(1, AdminRole::Super);

        try {
            service('adminAccount')->create(['login_id' => 'dev-admin', 'name' => '', 'role' => 'owner', 'password' => 'short']);
            $this->fail('通らないはず');
        } catch (ValidationException $e) {
            $this->assertSame(['name', 'role', 'login_id', 'password'], array_keys($e->errors));
        }
        // ID を含むパスワードも不可
        $this->expectException(ValidationException::class);
        service('adminAccount')->create(['login_id' => 'new-admin', 'name' => 'n', 'role' => 'general', 'password' => 'my-new-admin-pass']);
    }

    public function testLastSuperAdminIsProtected(): void
    {
        $this->actAs(1, AdminRole::Super);

        // 自分の権限は変えられない
        try {
            service('adminAccount')->update(1, ['name' => 'dev-admin', 'role' => 'general']);
            $this->fail('自分の権限は変えられないはず');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('role', $e->errors);
        }

        // 自分は削除できない
        try {
            service('adminAccount')->delete(1);
            $this->fail('自分は削除できないはず');
        } catch (ValidationException) {
        }
        // ほかの管理者は変えられ、削除できる
        service('adminAccount')->update(2, ['name' => '一般さん', 'role' => 'general']);
        $this->seeInDatabase('admins', ['id' => 2, 'name' => '一般さん']);
        service('adminAccount')->delete(2);
        $this->dontSeeInDatabase('admins', ['id' => 2]);
    }

    public function testCannotDemoteTheOnlyOtherSuperWhenItIsTheLast(): void
    {
        // 上位管理者が dev-admin だけのとき、別の上位管理者（id 1 を操作する人）として降格させると 0 人になる
        $this->actAs(99, AdminRole::Super);
        $this->expectException(ValidationException::class);
        service('adminAccount')->update(1, ['name' => 'dev-admin', 'role' => 'general']);
    }

    public function testResetPasswordUnlocksAccount(): void
    {
        $this->actAs(1, AdminRole::Super);
        $this->db->table('admins')->where('id', 2)->update(['locked_until' => '2099-01-01 00:00:00', 'failed_count' => 3]);
        service('adminAccount')->resetPassword(2, 'reset-password-123');
        $this->seeInDatabase('admins', ['id' => 2, 'locked_until' => null, 'failed_count' => 0]);
        $this->assertSame('dev-general', service('adminAuth')->verifyPassword('dev-general', 'reset-password-123')->login_id);
    }

    public function testChangeOwnPasswordRequiresCurrentPassword(): void
    {
        $this->actAs(2, AdminRole::General);

        try {
            service('adminAccount')->changeOwnPassword('wrong', 'brand-new-password');
            $this->fail('今のパスワードが違えば通らないはず');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('current_password', $e->errors);
        }
        service('adminAccount')->changeOwnPassword(DevSeeder::ADMIN_PASSWORD, 'brand-new-password');
        $this->assertSame('dev-general', service('adminAuth')->verifyPassword('dev-general', 'brand-new-password')->login_id);
        // この端末のログインは続く
        $this->assertNotNull(service('adminAuth')->current());
    }
}
