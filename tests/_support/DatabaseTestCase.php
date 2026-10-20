<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Database\Seeds\DevSeeder;
use App\Filters\BrowserId;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Filters;

/**
 * DB を使うテストの親（Feature テスト・Service のテスト）
 *
 * テストごとに tests グループ（SQLite :memory:）へマイグレーションと DevSeeder を流す。
 * - CSRF フィルタは外す（FeatureTestTrait はフィルタも通すため）
 * - サービスとキャッシュを空にする（レート制限・設定・NGワードのキャッシュがテストをまたがないように）
 * - 匿名ブラウザIDの Cookie を「dev-browser-me」にする（DevSeeder の回答 A-90412 を投稿したブラウザ）
 */
abstract class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    public const BROWSER_ID = 'dev-browser-me';
    public const HOST_TOKEN = 'dev-token-k7Qm2xPz';

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;

    /**
     * @var string
     */
    protected $namespace = 'App';

    protected $seed = DevSeeder::class;

    protected function setUp(): void
    {
        // 共有サービス（throttler など）が前のテストのキャッシュを掴んだままにならないよう、モック注入の前に全部捨てる
        $this->resetServices();
        parent::setUp();
        cache()->clean();
        $filters = config(Filters::class);
        unset($filters->globals['before']['csrf']);
        $_COOKIE[BrowserId::COOKIE_NAME] = self::BROWSER_ID;
    }

    protected function tearDown(): void
    {
        unset($_COOKIE[BrowserId::COOKIE_NAME]);
        parent::tearDown();
    }

    /**
     * 開催者としてリクエストする（X-Host-Token ヘッダー）
     */
    protected function asHost(string $token = self::HOST_TOKEN): static
    {
        return $this->withHeaders(['X-Host-Token' => $token]);
    }
}
