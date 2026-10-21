<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\HostService;
use CodeIgniter\I18n\Time;
use Tests\Support\DatabaseTestCase;

/**
 * U-08 開催者画面：回答が多くても古い回答まで選べる（100件ずつ「もっと見る」、いいねは上にまとめる。レビュー「取得上限」）
 *
 * @internal
 */
final class HostAnswersPagingTest extends DatabaseTestCase
{
    private const SLUG  = 'Zp3cW9aQ'; // 結果選択待ち（12466）
    private const TOKEN = 'dev-token-Zp3cW9aQ';

    protected function setUp(): void
    {
        parent::setUp();
        $rows = [];

        // 250件足す。いちばん古いものを OLDEST-ANSWER にする（本文は esc() で数値参照になるので英字）
        for ($i = 1; $i <= 250; $i++) {
            $at     = Time::now()->subMinutes($i)->toDateTimeString();
            $rows[] = ['oogiri_id' => 12466, 'body' => $i === 250 ? 'OLDEST-ANSWER' : 'BULK-' . $i, 'status' => 'visible', 'browser_id_hash' => 'b' . $i, 'ip_hash' => 'x', 'report_count' => 0, 'is_favorite' => $i === 200 ? 1 : 0, 'created_at' => $at, 'updated_at' => $at];
        }
        $this->db->table('answers')->insertBatch($rows);
    }

    public function testFirstPageShowsFavoritesAndAMoreLink(): void
    {
        $result = $this->asHost(self::TOKEN)->get('/host/' . self::SLUG);
        $result->assertOK();
        $result->assertSee('いいねした回答（1）');
        $result->assertSee('BULK-200'); // 古いがいいねしたので1ページ目に出る
        $result->assertSee('もっと見る');
        $result->assertDontSee('OLDEST-ANSWER');
        $this->assertSame(HostService::PER_PAGE + 1, substr_count($result->response()->getBody(), 'name="ranks[]"'), '一覧100件＋いいね1件');
    }

    public function testOldestAnswerCanBeReachedAndDecided(): void
    {
        $page3 = $this->asHost(self::TOKEN)->get('/host/' . self::SLUG . '?page=3');
        $page3->assertSee('OLDEST-ANSWER');
        $page3->assertDontSee('いいねした回答（');
        $page3->assertDontSee('もっと見る');

        $oldest = $this->db->table('answers')->where('body', 'OLDEST-ANSWER')->get()->getRow();
        $this->asHost(self::TOKEN)->post('/host/' . self::SLUG . '/result', ['ranks' => [(string) $oldest->id]])->assertRedirectTo(site_url('o/' . self::SLUG));
        $this->seeInDatabase('answers', ['id' => $oldest->id, 'rank' => 1]);
    }
}
