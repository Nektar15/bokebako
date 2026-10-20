<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use Tests\Support\DatabaseTestCase;

/**
 * A-04：投稿元識別情報の列は上位管理者にだけ出ること（SPEC A-04）。DevSeeder の dev-admin（上位）と dev-general（一般）でログインして確かめる
 *
 * @internal
 */
final class AdminRoleTest extends DatabaseTestCase
{
    public function testSuperAdminSeesSourceColumn(): void
    {
        $result = $this->asAdmin(AdminRole::Super)->get('admin/answers');
        $result->assertOK();
        $result->assertSee('投稿元');
        $result->assertSee('ID ');
        $this->assertStringContainsString('c-table__cell--mono', $result->response()->getBody());
    }

    public function testGeneralAdminDoesNotSeeSourceColumn(): void
    {
        $result = $this->asAdmin(AdminRole::General)->get('admin/answers');
        $result->assertOK();
        $result->assertDontSee('投稿元');
        $this->assertStringNotContainsString('c-table__cell--mono', $result->response()->getBody());
        $result->assertSee('一般管理者');
    }
}
