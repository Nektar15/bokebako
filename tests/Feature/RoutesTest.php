<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

/**
 * 管理画面と API のルートが応答すること（ユーザー向けは FrontRoutesTest）
 *
 * @internal
 */
final class RoutesTest extends DatabaseTestCase
{
    #[DataProvider('provideAdminRouteRespondsInTesting')]
    public function testAdminRouteRespondsInTesting(string $path): void
    {
        $this->asAdmin()->get($path)->assertOK();
    }

    /**
     * @return list<array{string}>
     */
    public static function provideAdminRouteRespondsInTesting(): iterable
    {
        return [
            ['admin'], ['admin/oogiri'], ['admin/oogiri/12452'], ['admin/answers'], ['admin/moderation'],
            ['admin/ng-words'], ['admin/ads'], ['admin/ogp'], ['admin/inquiries'], ['admin/settings'],
        ];
    }

    public function testStatusApiReturnsEnvelope(): void
    {
        $result = $this->get('/api/v1/o/k7Qm2xPz/status');
        $result->assertOK();
        $result->assertJSONFragment(['ok' => true]);
    }

    public function testUnknownRouteIs404(): void
    {
        // FeatureTestTrait では 404 はレスポンスにならず例外のまま上がる
        $this->expectException(PageNotFoundException::class);
        $this->get('/no-such-page');
    }
}
