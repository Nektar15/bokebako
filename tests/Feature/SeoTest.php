<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

/**
 * SEO（指示書 第2章、ADR 0016）：robots.txt・サイトマップ・説明文・正式な URL
 *
 * @internal
 */
final class SeoTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    public function testRobotsPointsToSitemapAndHidesNoAdminPath(): void
    {
        $result = $this->get('/robots.txt');
        $result->assertOK();
        $this->assertStringContainsString('text/plain', $result->response()->getHeaderLine('Content-Type'));
        $body = (string) $result->getBody();
        $this->assertStringContainsString('Sitemap: ' . site_url('sitemap.xml'), $body);
        $this->assertStringContainsString('Disallow: /host/', $body);
        $this->assertStringNotContainsString(trim((string) env('app.adminPath', 'admin'), '/'), $body);
    }

    public function testSitemapListsStaticPagesAndFinishedOogiriOnly(): void
    {
        $result = $this->get('/sitemap.xml');
        $result->assertOK();
        $xml = simplexml_load_string((string) $result->response()->getBody()); // TestResponse::getBody() は HTML として読み直すので、生の本文を使う
        $this->assertNotFalse($xml);
        $locs = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

        $this->assertContains(site_url('guidelines'), $locs);
        $this->assertContains(site_url('o/q8Lr4nTb'), $locs); // 結果発表
        $this->assertContains(site_url('o/Bx2gHj8k'), $locs); // 結果未選出
        $this->assertNotContains(site_url('o/k7Qm2xPz'), $locs); // 募集中
        $this->assertNotContains(site_url('o/Wq8nRt2v'), $locs); // 非表示
        $this->assertNotContains(site_url('o/Ty9pQw1z'), $locs); // 削除
    }

    #[DataProvider('providePagesHaveDescriptionAndCanonical')]
    public function testPagesHaveDescriptionAndCanonical(string $url, string $canonical): void
    {
        $body = html_entity_decode((string) $this->get($url)->getBody(), ENT_QUOTES | ENT_HTML5);
        $this->assertSame(1, substr_count($body, 'name="description"'), $url);
        $this->assertSame(1, substr_count($body, 'rel="canonical"'), $url);
        $this->assertStringContainsString('<link rel="canonical" href="' . site_url($canonical) . '">', $body);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function providePagesHaveDescriptionAndCanonical(): iterable
    {
        yield 'トップ' => ['/', '/'];

        yield '規約' => ['/terms', 'terms'];

        yield 'プライバシー' => ['/privacy', 'privacy'];

        yield '問い合わせ' => ['/contact', 'contact'];

        yield '募集終了' => ['/o/Zp3cW9aQ', 'o/Zp3cW9aQ'];

        yield '結果の2ページ目' => ['/o/q8Lr4nTb?page=2', 'o/q8Lr4nTb'];

        yield '回答のシェア URL' => ['/o/k7Qm2xPz/a/90412', 'o/k7Qm2xPz/a/90412'];
    }
}
