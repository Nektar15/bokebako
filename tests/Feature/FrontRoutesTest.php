<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * ユーザー向け画面：各URLが期待する画面を返すこと（CODING_RULES 13章）
 * ダミーデータの状態は ?fixture= で切り替える（testing 環境でも有効）。
 *
 * @internal
 */
final class FrontRoutesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

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
            'U-03 開催完了'           => ['/o/k7Qm2xPz/created', '大喜利を<br>開催しました'],
            'U-04 回答（募集中）'     => ['/o/k7Qm2xPz', 'あなたのボケ'],
            'U-04 回答（回答0件）'    => ['/o/k7Qm2xPz?fixture=open_empty', '現在 <strong data-js-timer-count>0</strong>'],
            'U-04 回答シェア用'       => ['/o/k7Qm2xPz/a/90412', 'シェアされたボケ'],
            'U-06 回答完了'           => ['/o/k7Qm2xPz/done', '回答しました！'],
            'U-07 回答一覧'           => ['/o/k7Qm2xPz/answers', 'みんなのボケ'],
            'U-08 開催者（募集中）'   => ['/host/k7Qm2xPz', 'Xで再募集する'],
            'U-08 開催者（選択待ち）' => ['/host/k7Qm2xPz?fixture=awaiting', 'あなたのベスト3'],
            'U-08 合言葉入力'         => ['/host/k7Qm2xPz?fixture=recover', '開催者の確認'],
            'U-10 結果発表（3件）'    => ['/o/k7Qm2xPz?fixture=decided', '結果をXでシェアする'],
            'U-10 結果発表（1件）'    => ['/o/k7Qm2xPz?fixture=decided_one', '1位'],
            'U-11 募集終了'           => ['/o/k7Qm2xPz?fixture=awaiting', '主催者がベスト回答を選んでいます'],
            'U-12 結果未選出'         => ['/o/k7Qm2xPz?fixture=no_result', '選出はありませんでした'],
            'U-13 非表示'             => ['/o/k7Qm2xPz?fixture=hidden', '公開されていません'],
            'U-13 非表示（削除済み）' => ['/o/k7Qm2xPz?fixture=deleted', '公開されていません'],
            'U-14 紹介'               => ['/about', 'あそびかた'],
            'U-15 利用規約'           => ['/terms', '第1条'],
            'U-16 プライバシー'       => ['/privacy', '取得する情報'],
            'U-17 ガイドライン'       => ['/guidelines', 'やめてほしいこと'],
            'U-18 お問い合わせ'       => ['/contact', 'お問い合わせの種類'],
            'U-18 送信完了'           => ['/contact/done', '送信しました'],
        ];
    }

    public function testUnansweredBrowserIsRedirectedFromList(): void
    {
        $this->get('/o/k7Qm2xPz/answers?fixture=not_answered')->assertRedirectTo(site_url('o/k7Qm2xPz'));
    }

    public function testCreatedPageRequiresHostToken(): void
    {
        $this->get('/o/k7Qm2xPz/created?fixture=recover')->assertRedirectTo(site_url('o/k7Qm2xPz'));
    }

    public function testDecidedOogiriHostPageGoesToResult(): void
    {
        $this->get('/host/k7Qm2xPz?fixture=decided')->assertRedirectTo(site_url('o/k7Qm2xPz'));
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
        $result->assertJSONFragment(['ok' => true, 'data' => ['status' => 'open', 'answerCount' => 47]]);
    }
}
