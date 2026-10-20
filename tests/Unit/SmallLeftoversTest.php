<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entities\AdminUser;
use App\Enums\AdminRole;
use App\Enums\OgpKind;
use App\Services\OgpService;
use Tests\Support\DatabaseTestCase;

/**
 * 小さな積み残し：通知メールのテンプレート、OGP 画像の一括の作り直し（A-08）
 *
 * @internal
 */
final class SmallLeftoversTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        service('currentAdmin')->set(new AdminUser(1, 'test-admin', AdminRole::Super));
        $this->removeReservation();
    }

    protected function tearDown(): void
    {
        $this->removeReservation();
        parent::tearDown();
    }

    public function testMailIsComposedFromTemplate(): void
    {
        $mail = service('mail')->compose('report', ['target' => '回答', 'id' => 90412, 'reason' => '個人情報', 'url' => 'https://example.com/admin/moderation?item=answer-90412']);
        $this->assertSame('[ぼけばこ] 通報を受け付けました（個人情報）', $mail['subject']);
        $this->assertStringContainsString('回答 90412 が通報されました（理由：個人情報）', $mail['body']);
        $this->assertStringContainsString('https://example.com/admin/moderation?item=answer-90412', $mail['body']);
        $this->assertStringContainsString('自動で送っています', $mail['body']);
        $this->assertStringNotContainsString('{', $mail['body'], '差し込み漏れがない');
    }

    public function testEveryTemplateFillsAllPlaceholders(): void
    {
        $values = ['target' => 'x', 'id' => 1, 'reason' => 'x', 'count' => 3, 'type' => 'x', 'detail' => 'x'];

        foreach (['report', 'autoHidden', 'contact', 'ngReview'] as $template) {
            $mail = service('mail')->compose($template, $values);
            $this->assertStringNotContainsString('{', $mail['subject'] . $mail['body'], $template);
        }
    }

    public function testBulkRegenerationProceedsInBatchesAndFinishes(): void
    {
        $ogp = service('ogp');
        $this->assertNull($ogp->bulkStatus());

        $answerImage = $ogp->path(OgpKind::Answer, '90412');
        if (! is_dir(dirname($answerImage))) {
            mkdir(dirname($answerImage), 0775, true);
        }
        file_put_contents($answerImage, 'old-template');

        $ogp->requestRegenerateAll();
        $total = $ogp->bulkStatus()['remaining'] ?? 0;
        $this->assertGreaterThan(2, $total);

        $this->assertSame(2, $ogp->regenerateNextBatch(2));
        $this->assertSame($total - 2, $ogp->bulkStatus()['remaining'] ?? null);

        // 最後まで進めると予約が消える。古いテンプレートの回答シェア画像も消える（次のアクセスで作り直し）
        while ($ogp->bulkStatus() !== null) {
            $ogp->regenerateNextBatch(OgpService::BULK_BATCH);
        }
        $this->assertFileDoesNotExist($answerImage);
        $this->seeInDatabase('admin_logs', ['target_type' => 'ogp', 'what' => '公開中の画像の作り直しが終わりました']);
    }

    private function removeReservation(): void
    {
        if (is_file(WRITEPATH . 'ogp-templates/regenerate-all.json')) {
            unlink(WRITEPATH . 'ogp-templates/regenerate-all.json');
        }
    }
}
