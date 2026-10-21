<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use Config\ScreenNav;
use Tests\Support\DatabaseTestCase;

/**
 * 画面一覧ナビ（確認用。ADR 0009）：既定では出ない・ルートもない。有効にすると全画面へ移動できる
 *
 * @internal
 */
final class ScreenNavTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        config(ScreenNav::class)->enabled = false;
        parent::tearDown();
    }

    /**
     * 行き先の一覧から、名前で番号を探す
     */
    private function indexOf(string $label): int
    {
        foreach (service('screenNav')->groups() as $group) {
            foreach ($group['options'] as $option) {
                if ($option['label'] === $label) {
                    return $option['index'];
                }
            }
        }
        $this->fail("{$label} がない");
    }

    public function testHiddenByDefault(): void
    {
        $this->get('/')->assertDontSee('全画面へ移動');
        $this->expectException(PageNotFoundException::class);
        $this->get('_screens/go?to=0');
    }

    public function testShownWhenEnabled(): void
    {
        config(ScreenNav::class)->enabled = true;
        $result                           = $this->get('/');
        $result->assertSee('全画面へ移動');
        $result->assertSee('U-12 結果未選出');
        $result->assertSee('A-10 運用設定');
        $this->get('/o/q8Lr4nTb')->assertSee('全画面へ移動');
    }

    public function testEveryPlainUrlOpens(): void
    {
        config(ScreenNav::class)->enabled = true;

        foreach (config(ScreenNav::class)->items as $items) {
            foreach ($items as [$label, $target]) {
                if (str_starts_with($target, 'url:')) {
                    $this->get(substr($target, 4))->assertOK();
                }
            }
        }
    }

    public function testJumpAsHostOpensHostPage(): void
    {
        config(ScreenNav::class)->enabled = true;
        $result                           = $this->get('_screens/go?to=' . $this->indexOf('U-08 開催者（結果選択待ち）'));
        $result->assertRedirectTo(site_url('host/Zp3cW9aQ'));
        $this->assertTrue($result->response()->hasCookie('bk_host_Zp3cW9aQ'));
    }

    public function testJumpAsAnswererPreparesDonePage(): void
    {
        config(ScreenNav::class)->enabled = true;
        $result                           = $this->get('_screens/go?to=' . $this->indexOf('U-06 回答完了'));
        $result->assertRedirectTo(site_url('o/k7Qm2xPz/done'));
        $this->assertTrue($result->response()->hasCookie('bk_bid'));
        $this->assertSame(90412, (int) session('bk_last_answer:k7Qm2xPz'));
    }

    public function testUnknownIndexIs404(): void
    {
        config(ScreenNav::class)->enabled = true;
        $this->expectException(PageNotFoundException::class);
        $this->get('_screens/go?to=999');
    }
}
