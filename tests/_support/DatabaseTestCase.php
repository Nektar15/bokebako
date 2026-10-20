<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Database\Seeds\DevSeeder;
use App\Enums\AdminRole;
use App\Filters\BrowserId;
use App\Models\AdminModel;
use App\Services\AdminAuthService;
use CodeIgniter\Cache\ResponseCache;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Cache;
use Config\Filters;
use Config\Services;

/**
 * DB を使うテストの親（Feature テスト・Service のテスト）
 *
 * テストごとに tests グループ（SQLite :memory:）へマイグレーションと DevSeeder を流す。
 * - CSRF フィルタは外す（FeatureTestTrait はフィルタも通すため）
 * - サービスとキャッシュを空にする（レート制限・設定・NGワード・ページキャッシュがテストをまたがないように）
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
        // ページキャッシュ（ResponseCache）はアプリ生成時に本物のファイルキャッシュを掴むため、テスト用のキャッシュに差し替える。
        // 差し替えないと writable/cache/ に結果ページが残り、別のテストや次の実行で古い HTML が返る
        // アプリも作り直し、リクエストの始めに TTL を 0 に戻す処理が同じインスタンスを使うようにする（本番と同じ動き）
        Services::injectMock('responsecache', new ResponseCache(config(Cache::class), cache()));
        $this->app = $this->createApplication();
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
     * 管理者としてログインした状態でリクエストする（A-01。DevSeeder の dev-admin（上位）/ dev-general（一般））
     */
    protected function asAdmin(AdminRole $role = AdminRole::Super): static
    {
        $account = model(AdminModel::class)->where('login_id', $role === AdminRole::Super ? 'dev-admin' : 'dev-general')->first();
        $now     = time();

        return $this->withSession([
            AdminAuthService::SESSION_ID          => $account->id,
            AdminAuthService::SESSION_FINGERPRINT => AdminAuthService::fingerprint($account),
            AdminAuthService::SESSION_LOGIN_AT    => $now,
            AdminAuthService::SESSION_SEEN_AT     => $now,
        ]);
    }

    /**
     * 開催者としてリクエストする（X-Host-Token ヘッダー）
     */
    protected function asHost(string $token = self::HOST_TOKEN): static
    {
        return $this->withHeaders(['X-Host-Token' => $token]);
    }
}
