<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * みんなのボケ（U-07）の並び替え（BUG-009、ADR 0015）：新着順・古い順。同じ日時は ID で順を決める
 *
 * @internal
 */
final class AnswerSortTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->post('/o/k7Qm2xPz/answers', ['body' => '並び替えの確認'])->assertRedirect(); // 回答済みにする（U-07 を開ける）
    }

    /**
     * @return list<string> 画面に出た回答の本文（上から順）
     */
    private function bodies(string $url): array
    {
        preg_match_all('#<p class="c-answers__body">(.*?)</p>#su', (string) $this->get($url)->getBody(), $m);

        return array_map(static fn (string $b): string => html_entity_decode(strip_tags($b)), $m[1]);
    }

    /**
     * @return list<string> DB の回答の本文（指定の順）
     */
    private function expected(string $direction): array
    {
        return array_column($this->db->table('answers')->select('body')->where('oogiri_id', 12480)->where('status', 'visible')
            ->orderBy('created_at', $direction)->orderBy('id', $direction)->get()->getResultArray(), 'body');
    }

    public function testDefaultIsNewestFirst(): void
    {
        $bodies = $this->bodies('/o/k7Qm2xPz/answers');
        $this->assertSame('並び替えの確認', $bodies[0]);
        $this->assertSame($this->expected('DESC'), $bodies);
    }

    public function testOldestFirst(): void
    {
        $this->assertSame($this->expected('ASC'), $this->bodies('/o/k7Qm2xPz/answers?sort=old'));
    }

    public function testUnknownSortFallsBackToNewest(): void
    {
        $this->assertSame($this->expected('DESC'), $this->bodies('/o/k7Qm2xPz/answers?sort=%27%3Bdrop'));
    }

    public function testSameTimestampIsStable(): void
    {
        $now = Time::now()->toDateTimeString();
        $this->db->table('answers')->where('oogiri_id', 12480)->update(['created_at' => $now]);
        $first = $this->bodies('/o/k7Qm2xPz/answers?sort=old');
        $this->assertSame($first, $this->bodies('/o/k7Qm2xPz/answers?sort=old'));
        $this->assertSame(array_reverse($first), $this->bodies('/o/k7Qm2xPz/answers'));
    }

    public function testCurrentSortIsMarkedAndMoreLinkKeepsSort(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->db->table('answers')->insert(['oogiri_id' => 12480, 'body' => "追加{$i}", 'status' => 'visible', 'browser_id_hash' => 'x', 'ip_hash' => 'x', 'created_at' => Time::now()->subMinutes($i + 100)->toDateTimeString()]);
        }
        $html = (string) $this->get('/o/k7Qm2xPz/answers?sort=old')->getBody();
        $this->assertMatchesRegularExpression('#c-segment__item is-current"[^>]*sort=old#', $html);
        $this->assertStringContainsString('/o/k7Qm2xPz/answers?sort=old&amp;page=2', $html);
    }
}
