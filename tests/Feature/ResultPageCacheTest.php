<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\PageCacheService;
use CodeIgniter\HTTP\URI;
use CodeIgniter\I18n\Time;
use Tests\Support\DatabaseTestCase;

/**
 * 結果ページ（U-10・U-12）のページキャッシュ：個人の情報を載せない、?page= を取り違えない（レビュー指摘 2・5）
 *
 * @internal
 */
final class ResultPageCacheTest extends DatabaseTestCase
{
    private const SLUG = 'q8Lr4nTb'; // 確定済み（12452）

    protected function setUp(): void
    {
        parent::setUp();
        $now = Time::now()->addHours(1)->toDateTimeString(); // 1ページ目の先頭に出るよう、DevSeeder のどの回答よりも新しくする
        // このブラウザ（dev-browser-me）の回答と、2ページ目ができるだけの古い回答を足す（本文は esc() で数値参照になるので英字にする）
        $this->db->table('answers')->insert(['oogiri_id' => 12452, 'body' => 'MY-ANSWER', 'status' => 'visible', 'browser_id_hash' => service('identity')->hash(self::BROWSER_ID), 'ip_hash' => 'x', 'report_count' => 0, 'created_at' => $now, 'updated_at' => $now]);

        for ($i = 1; $i <= 25; $i++) {
            $at = Time::now()->subDays(3)->subMinutes($i)->toDateTimeString();
            $this->db->table('answers')->insert(['oogiri_id' => 12452, 'body' => 'OLD-ANSWER-' . $i, 'status' => 'visible', 'browser_id_hash' => 'y', 'ip_hash' => 'y', 'report_count' => 0, 'created_at' => $at, 'updated_at' => $at]);
        }
    }

    public function testCachedResultPageHasNoPerBrowserMark(): void
    {
        $result = $this->get('o/' . self::SLUG);
        $result->assertOK();
        $result->assertSee('MY-ANSWER');
        // 共有キャッシュに載るので「あなたの回答」は付けない（付けると他の人にも出る）
        $result->assertDontSee('あなたの回答');
        $this->assertNotNull(cache(PageCacheService::key('o/' . self::SLUG)), '1ページ目はキャッシュされる');
    }

    public function testSecondPageIsNotServedFromFirstPageCache(): void
    {
        $this->get('o/' . self::SLUG)->assertOK(); // 1ページ目をキャッシュさせる
        $second = $this->get('o/' . self::SLUG . '?page=2');
        $second->assertOK();
        $second->assertSee('OLD-ANSWER-25');
        $second->assertDontSee('MY-ANSWER');
        // ?page= 付きは保存しない（purgeOogiri() で捨てられないキャッシュを作らない）
        $pageTwoKey = md5('GET:' . (new URI(site_url('o/' . self::SLUG)))->setQuery('page=2'));
        $this->assertNotSame(PageCacheService::key('o/' . self::SLUG), $pageTwoKey);
        $this->assertNull(cache($pageTwoKey));
    }
}
