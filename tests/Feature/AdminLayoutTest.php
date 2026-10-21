<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

/**
 * 管理画面のスマホ・タブレット対応（ADR 0010）：引き出しメニューと、表をカードにするための列名
 *
 * @internal
 */
final class AdminLayoutTest extends DatabaseTestCase
{
    public function testLayoutHasDrawerMenu(): void
    {
        $body = (string) $this->asAdmin()->get('admin/oogiri')->getBody();

        // チェックボックスと <label> で開け閉めする（JS なしでも動く）
        $this->assertStringContainsString('<input type="checkbox" id="admin-menu"', $body);
        $this->assertStringContainsString('<label for="admin-menu" class="l-topbar__menu"', $body);
        $this->assertStringContainsString('<aside class="l-sidebar" id="admin-sidebar">', $body);
        $this->assertStringContainsString('<label for="admin-menu" class="l-sidebar__close"', $body);
    }

    public function testTableCellsCarryColumnNames(): void
    {
        $body = (string) $this->asAdmin()->get('admin/oogiri')->getBody();

        $this->assertMatchesRegularExpression('/class="c-table__cell[^"]*" role="cell" data-label="PV"/', $body);
        $this->assertStringContainsString('data-label="ID"', $body);
    }

    public function testListCardsJumpToDetail(): void
    {
        $body = (string) $this->asAdmin()->get('admin/moderation')->getBody();

        $this->assertStringContainsString('#detail"', $body);
        $this->assertStringContainsString('<section id="detail"', $body);
    }
}
