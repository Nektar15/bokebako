<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OgpKind;
use Tests\Support\DatabaseTestCase;

/**
 * Vercel の OGP 画像生成に渡す中身（GET /api/v1/ogp/{kind}/{key}。ADR 0014）
 *
 * @internal
 */
final class OgpDataApiTest extends DatabaseTestCase
{
    public function testResultDataHasTopThree(): void
    {
        $result = $this->get('api/v1/ogp/result/q8Lr4nTb');
        $result->assertOK();
        $data = json_decode((string) $result->response()->getBody(), true)['data'];
        $this->assertSame([1, 2, 3], array_column($data['ranked'], 'rank'));
        $this->assertSame('結果発表', $data['texts']['result_stamp']);
    }

    public function testAnswerAndRecruitData(): void
    {
        $this->get('api/v1/ogp/answer/90412')->assertOK();
        $this->get('api/v1/ogp/recruit/k7Qm2xPz')->assertOK();
        $this->get('api/v1/ogp/recruit/preview')->assertOK();
    }

    public function testHiddenOrUnknownIs404(): void
    {
        $this->get('api/v1/ogp/result/Wq8nRt2v')->assertStatus(404); // 非表示
        $this->get('api/v1/ogp/result/Q8LR4NTB')->assertStatus(404); // 大文字小文字違い
        $this->get('api/v1/ogp/nope/q8Lr4nTb')->assertStatus(404);
        $this->get('api/v1/ogp/answer/90301')->assertStatus(404);   // 削除済みの回答
    }

    public function testUrlUsesVercelRendererWhenConfigured(): void
    {
        putenv('ogp.renderer=vercel');
        $_ENV['ogp.renderer'] = 'vercel';

        try {
            $this->assertStringContainsString('og-image/result/q8Lr4nTb.png?v=', service('ogp')->url(OgpKind::Result, 'q8Lr4nTb'));
        } finally {
            putenv('ogp.renderer');
            unset($_ENV['ogp.renderer']);
        }
    }
}
