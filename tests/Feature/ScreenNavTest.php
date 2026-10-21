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
     * モーダルの一覧から、名前でリンク先を探す
     */
    private function hrefOf(string $label): string
    {
        foreach (service('screenNav')->groups() as $group) {
            foreach ($group['options'] as $option) {
                if ($option['label'] === $label) {
                    return $option['href'];
                }
            }
        }
        $this->fail("{$label} がない");
    }

    /**
     * site_url() の絶対 URL から、リクエスト用のパスにする
     */
    private function pathOf(string $href): string
    {
        return substr($href, strlen(site_url('')));
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
        // 画面 ID と名前は分けて表示する
        $this->assertStringContainsString('<span class="c-screen-nav__id">U-12</span>', (string) $result->getBody());
        $result->assertSee('結果未選出');
        $result->assertSee('運用設定');
        // そのまま開ける画面はふつうのリンク、開催者として開く画面は /_screens/go を通る
        $this->assertSame(site_url('o/Bx2gHj8k'), $this->hrefOf('U-12 結果未選出'));
        $this->assertStringContainsString('_screens/go?to=', $this->hrefOf('U-03 開催完了'));
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
        $result                           = $this->get($this->pathOf($this->hrefOf('U-08 開催者（結果選択待ち）')));
        $result->assertRedirectTo(site_url('host/Zp3cW9aQ'));
        $this->assertTrue($result->response()->hasCookie('bk_host_Zp3cW9aQ'));
    }

    public function testJumpAsAnswererPreparesDonePage(): void
    {
        config(ScreenNav::class)->enabled = true;
        $result                           = $this->get($this->pathOf($this->hrefOf('U-06 回答完了')));
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
