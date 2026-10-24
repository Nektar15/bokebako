<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

/**
 * X のカード用のメタタグ（BUG-001、ADR 0015）。JS を動かす前の HTML にあること
 *
 * @internal
 */
final class OgpMetaTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /**
     * @return array<string, string> property / name => content
     */
    private function metas(string $url): array
    {
        preg_match_all('#<meta (?:property|name)="((?:og|twitter):[^"]+)" content="([^"]*)"#u', (string) $this->get($url)->getBody(), $m, PREG_SET_ORDER);
        $metas = [];

        foreach ($m as [$_, $key, $content]) {
            $metas[$key] ??= html_entity_decode($content, ENT_QUOTES | ENT_HTML5);
        }

        return $metas;
    }

    #[DataProvider('provideCardTagsArePresent')]
    public function testCardTagsArePresent(string $url, string $image): void
    {
        $metas = $this->metas($url);

        foreach (['og:title', 'og:description', 'og:image', 'og:url', 'og:site_name', 'twitter:card'] as $key) {
            $this->assertNotSame('', $metas[$key] ?? '', "{$url} に {$key} がない");
        }
        $this->assertSame('summary_large_image', $metas['twitter:card']);
        $this->assertStringStartsWith('http', $metas['og:image']);
        // 画像がまだ作られていなければ既定の画像（OgpService::url()）。テスト環境では生成しないものがある
        $this->assertMatchesRegularExpression('#(' . preg_quote($image, '#') . '|assets/img/ogp-default\.png)#', $metas['og:image']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideCardTagsArePresent(): iterable
    {
        yield 'トップ' => ['/', 'assets/img/ogp-default.png'];

        yield '募集中' => ['/o/k7Qm2xPz', 'og-image/recruit/k7Qm2xPz'];

        yield '回答（募集中）' => ['/o/k7Qm2xPz/a/90412', 'og-image/answer/90412'];

        yield '結果' => ['/o/q8Lr4nTb', 'og-image/result/q8Lr4nTb'];

        yield '規約' => ['/terms', 'assets/img/ogp-default.png'];
    }

    public function testAnswerPageDescribesTheAnswer(): void
    {
        $body = (string) $this->db->table('answers')->where('id', 90412)->get()->getRow()->body;
        $this->assertSame(mb_strimwidth($body, 0, 120, '…'), $this->metas('/o/k7Qm2xPz/a/90412')['og:description']);
    }

    public function testOnlyOneOgImagePerPage(): void
    {
        $this->assertSame(1, substr_count((string) $this->get('/o/k7Qm2xPz')->getBody(), 'property="og:image"'));
    }
}
