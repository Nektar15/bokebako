<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * 管理画面と API のルートが応答すること（ユーザー向けは FrontRoutesTest）
 *
 * @internal
 */
final class RoutesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    #[DataProvider('provideAdminRouteRespondsInTesting')]
    public function testAdminRouteRespondsInTesting(string $path): void
    {
        // testing 環境は development と同じく仮管理者で通る
        $this->get($path)->assertOK();
    }

    /**
     * @return list<array{string}>
     */
    public static function provideAdminRouteRespondsInTesting(): iterable
    {
        return [
            ['admin'], ['admin/oogiri'], ['admin/oogiri/1'], ['admin/answers'], ['admin/moderation'],
            ['admin/ng-words'], ['admin/ads'], ['admin/ogp'], ['admin/inquiries'], ['admin/settings'],
        ];
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
