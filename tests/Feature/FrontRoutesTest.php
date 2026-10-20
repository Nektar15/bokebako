<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AnswerService;
use CodeIgniter\Exceptions\PageNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

/**
 * ユーザー向け画面：各URLが期待する画面を返すこと（CODING_RULES 13章）
 *
 * データは DevSeeder（SQLite :memory:）。状態ごとの大喜利は次のスラッグ：
 * 募集中 k7Qm2xPz / 回答なし Ee4fGg5h / 選択待ち Zp3cW9aQ / 確定（3件）q8Lr4nTb / 確定（1件）Cv5tYu7i
 * 未選出 Bx2gHj8k / 非表示 Wq8nRt2v / 削除済み Ty9pQw1z
 *
 * @internal
 */
final class FrontRoutesTest extends DatabaseTestCase
{
    #[DataProvider('provideScreen')]
    public function testScreen(string $path, string $expected): void
    {
        $result = $this->get($path);
        $result->assertOK();
        $result->assertSee($expected);
    }

    /**
     * @return array<string, array{string, string}> URL と、その画面にだけある文言
     */
    public static function provideScreen(): iterable
    {
        return [
            'U-01 トップ'             => ['/', '24時間大喜利を開催する'],
            'U-01 派生元つき'         => ['/?from=abc', 'value="abc"'],
            'U-04 回答（募集中）'     => ['/o/k7Qm2xPz', 'あなたのボケ'],
            'U-04 回答（回答0件）'    => ['/o/Ee4fGg5h', '現在 <strong data-js-timer-count>0</strong>'],
            'U-04 回答シェア用'       => ['/o/k7Qm2xPz/a/90412', 'シェアされたボケ'],
            'U-07 回答一覧'           => ['/o/k7Qm2xPz/answers', 'みんなのボケ'],
            'U-08 合言葉入力'         => ['/host/Zp3cW9aQ', '開催者の確認'],
            'U-10 結果発表（3件）'    => ['/o/q8Lr4nTb', '結果をXでシェアする'],
            'U-10 結果発表（1件）'    => ['/o/Cv5tYu7i', '1位'],
            'U-11 募集終了'           => ['/o/Zp3cW9aQ', '主催者がベスト回答を選んでいます'],
            'U-12 結果未選出'         => ['/o/Bx2gHj8k', '選出はありませんでした'],
            'U-13 非表示'             => ['/o/Wq8nRt2v', '公開されていません'],
            'U-13 非表示（削除済み）' => ['/o/Ty9pQw1z', '公開されていません'],
            'U-14 紹介'               => ['/about', 'あそびかた'],
            'U-15 利用規約'           => ['/terms', '第1条'],
            'U-16 プライバシー'       => ['/privacy', '取得する情報'],
            'U-17 ガイドライン'       => ['/guidelines', 'やめてほしいこと'],
            'U-18 お問い合わせ'       => ['/contact', 'お問い合わせの種類'],
            'U-18 送信完了'           => ['/contact/done', '送信しました'],
        ];
    }

    #[DataProvider('provideHostScreen')]
    public function testHostScreen(string $path, string $token, string $expected): void
    {
        $result = $this->asHost($token)->get($path);
        $result->assertOK();
        $result->assertSee($expected);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function provideHostScreen(): iterable
    {
        return [
            'U-03 開催完了'           => ['/o/k7Qm2xPz/created', 'dev-token-k7Qm2xPz', '大喜利を<br>開催しました'],
            'U-08 開催者（募集中）'   => ['/host/k7Qm2xPz', 'dev-token-k7Qm2xPz', 'Xで再募集する'],
            'U-08 開催者（選択待ち）' => ['/host/Zp3cW9aQ', 'dev-token-Zp3cW9aQ', 'あなたのベスト3'],
        ];
    }

    public function testAnswerDonePageAfterPosting(): void
    {
        $this->post('/o/k7Qm2xPz/answers', ['body' => 'ボケ'])->assertRedirectTo(site_url('o/k7Qm2xPz/done'));
        // FeatureTestTrait はリクエストごとにセッションを作り直すので、投稿直後のセッションを明示して開く
        $result = $this->withSession([AnswerService::SESSION_LAST . ':k7Qm2xPz' => 90412])->get('/o/k7Qm2xPz/done');
        $result->assertOK();
        $result->assertSee('回答しました！');
    }

    public function testUnansweredBrowserIsRedirectedFromList(): void
    {
        $_COOKIE['bk_bid'] = 'never-answered';
        $this->get('/o/k7Qm2xPz/answers')->assertRedirectTo(site_url('o/k7Qm2xPz'));
    }

    public function testCreatedPageRequiresHostToken(): void
    {
        $this->get('/o/k7Qm2xPz/created')->assertRedirectTo(site_url('o/k7Qm2xPz'));
    }

    public function testWrongHostTokenShowsRecover(): void
    {
        $result = $this->asHost('wrong')->get('/host/k7Qm2xPz');
        $result->assertOK();
        $result->assertSee('開催者の確認');
    }

    public function testDecidedOogiriHostPageGoesToResult(): void
    {
        $this->asHost('dev-token-q8Lr4nTb')->get('/host/q8Lr4nTb')->assertRedirectTo(site_url('o/q8Lr4nTb'));
    }

    public function testUnknownSlugIs404(): void
    {
        // FeatureTestTrait では 404 はレスポンスにならず例外のまま上がる
        $this->expectException(PageNotFoundException::class);
        $this->get('/o/none');
    }

    public function testStatusApi(): void
    {
        $result = $this->get('/api/v1/o/k7Qm2xPz/status');
        $result->assertOK();
        $result->assertJSONFragment(['ok' => true, 'data' => ['status' => 'open', 'answerCount' => 9]]);
    }

    public function testEventApiRecordsPv(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/events', ['name' => 'pv', 'slug' => 'k7Qm2xPz']);
        $result->assertOK();
        $this->seeInDatabase('events', ['name' => 'pv', 'oogiri_id' => 12480]);
    }

    public function testReportApiCountsUp(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/reports', ['slug' => 'k7Qm2xPz', 'type' => 'answer', 'id' => '90411', 'reason' => 'abuse']);
        $result->assertOK();
        $this->seeInDatabase('reports', ['target_type' => 'answer', 'target_id' => 90411]);
        $this->seeInDatabase('answers', ['id' => 90411, 'report_count' => 1]);
    }
}
