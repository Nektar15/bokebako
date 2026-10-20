<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * A-04：投稿元識別情報の列は上位管理者にだけ出ること（SPEC A-04、CODING_RULES 7-1）
 *
 * 仮認証では ?role=general で一般管理者に落とせる（development / testing 限定）。
 *
 * @internal
 */
final class AdminRoleTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testSuperAdminSeesSourceColumn(): void
    {
        $result = $this->get('admin/answers');
        $result->assertOK();
        $result->assertSee('投稿元');
        $result->assertSee('3fa1…9c0e');
    }

    public function testGeneralAdminDoesNotSeeSourceColumn(): void
    {
        $result = $this->get('admin/answers?role=general');
        $result->assertOK();
        $result->assertDontSee('投稿元');
        $result->assertDontSee('3fa1…9c0e');
        $result->assertSee('一般管理者');
    }
}
