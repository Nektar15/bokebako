<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * 全 GET ルートが 200 を返すこと（CODING_RULES 13章）
 * 工程1・2で View ができたら、表示される View の確認を各テストに足す。
 */
final class RoutesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** @return list<array{string}> */
    public static function frontRoutes(): array
    {
        return [
            ['/'], ['/o/demo'], ['/o/demo/created'], ['/o/demo/done'], ['/o/demo/answers'], ['/o/demo/a/1'],
            ['/about'], ['/terms'], ['/privacy'], ['/guidelines'], ['/contact'], ['/contact/done'],
            ['/host/demo'],
        ];
    }

    /** @return list<array{string}> */
    public static function adminRoutes(): array
    {
        return [
            ['admin'], ['admin/oogiri'], ['admin/oogiri/1'], ['admin/answers'], ['admin/moderation'],
            ['admin/ng-words'], ['admin/ads'], ['admin/ogp'], ['admin/inquiries'], ['admin/settings'],
        ];
    }

    /** @dataProvider frontRoutes */
    public function testFrontRouteResponds(string $path): void
    {
        $this->get($path)->assertOK();
    }

    /** @dataProvider adminRoutes */
    public function testAdminRouteRespondsInTesting(string $path): void
    {
        // testing 環境は development と同じく仮管理者で通る
        $this->get($path)->assertOK();
    }

    public function testStatusApiReturnsEnvelope(): void
    {
        $this->get('/api/v1/o/demo/status')->assertOK()->assertJSONFragment(['ok' => true]);
    }

    public function testUnknownRouteIs404(): void
    {
        $this->get('/no-such-page')->assertStatus(404);
    }
}
