<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * ブラッシュアップ（第11章・第12章、ADR 0015）で直したところ
 *
 * @internal
 */
final class BrushupTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    public function testCreatedPreviewFollowsRecruitTemplate(): void
    {
        $this->db->table('settings')->insert(['name' => 'tpl_recruit', 'value' => "大喜利の時間です\n{odai}\n{url}"]);
        cache()->clean();
        $body = html_entity_decode((string) $this->asHost()->get('/o/k7Qm2xPz/created')->getBody(), ENT_QUOTES | ENT_HTML5);
        $this->assertMatchesRegularExpression('#<p class="p-created__post-text">大喜利の時間です<br>\s*こんなコンビニ店員は嫌だ<br>\s*\#ぼけばこ</p>#u', $body);
    }

    public function testHostDoesNotSeeHostEntryLinkOnOwnAnswerPage(): void
    {
        $this->asHost()->get('/o/k7Qm2xPz')->assertDontSee('別の端末から開催者画面を開く');
    }

    public function testVisitorSeesHostEntryLink(): void
    {
        $this->get('/o/k7Qm2xPz')->assertSee('別の端末から開催者画面を開く');
    }

    public function testResultShareHasNativeShareButton(): void
    {
        $this->assertStringContainsString('data-js-web-share', (string) $this->get('/o/q8Lr4nTb')->getBody());
    }
}
