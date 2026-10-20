<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use Tests\Support\DatabaseTestCase;

/**
 * A-01 管理者アカウントの画面：上位管理者は一覧と追加、一般管理者は自分のパスワード変更だけ
 *
 * @internal
 */
final class AdminAccountPageTest extends DatabaseTestCase
{
    public function testSuperAdminSeesListAndForm(): void
    {
        $result = $this->asAdmin(AdminRole::Super)->get('admin/accounts');
        $result->assertOK();
        $result->assertSee('管理者を追加');
        $result->assertSee('dev-general');
        $result->assertSee('自分のパスワードを変更');
    }

    public function testEditFormIsShown(): void
    {
        $result = $this->asAdmin(AdminRole::Super)->get('admin/accounts?edit=2');
        $result->assertOK();
        $result->assertSee('管理者を編集');
        $result->assertSee('パスワードを再設定');
    }

    public function testGeneralAdminSeesOnlyOwnPasswordForm(): void
    {
        $result = $this->asAdmin(AdminRole::General)->get('admin/accounts');
        $result->assertOK();
        $result->assertDontSee('管理者を追加');
        $result->assertDontSee('dev-admin（');
        $result->assertSee('自分のパスワードを変更');
    }

    public function testGeneralAdminCannotPostAccountChanges(): void
    {
        $result = $this->asAdmin(AdminRole::General)->post('admin/accounts', ['login_id' => 'evil', 'name' => 'e', 'role' => 'super', 'password' => 'long-enough-pass']);
        $result->assertRedirect();
        $this->dontSeeInDatabase('admins', ['login_id' => 'evil']);
    }

    public function testSuperAdminAddsAccount(): void
    {
        $this->asAdmin(AdminRole::Super)->post('admin/accounts', ['login_id' => 'new-admin', 'name' => '新しい人', 'role' => 'general', 'password' => 'long-enough-pass'])
            ->assertRedirectTo(site_url('admin/accounts'));
        $this->seeInDatabase('admins', ['login_id' => 'new-admin', 'role' => 'general']);
    }
}
