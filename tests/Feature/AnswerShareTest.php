<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

/**
 * 回答のシェア（BUG-004・BUG-008、ADR 0015）
 *
 * @internal
 */
final class AnswerShareTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /**
     * ページ内の X 投稿 URL の本文（回答の行）を、回答本文 => 投稿文の行 で返す
     *
     * @return array<string, string>
     */
    private function answerShareLines(string $html): array
    {
        preg_match_all('#https://x\.com/intent/post\?text=([^"&]+)#', html_entity_decode($html), $m);
        $lines = [];

        foreach ($m[1] as $encoded) {
            $text = rawurldecode($encoded);

            if (preg_match('/^(俺の回答|このボケ好き)：(.+)$/mu', $text, $line) === 1) {
                $lines[$line[2]] = $line[1];
            }
        }

        return $lines;
    }

    public function testOwnAnswerSaysMineAndOthersSayThirdPerson(): void
    {
        $this->post('/o/k7Qm2xPz/answers', ['body' => '自分のボケです'])->assertRedirect();
        $html  = (string) $this->get('/o/k7Qm2xPz/answers')->getBody();
        $lines = $this->answerShareLines($html);

        $this->assertSame('俺の回答', $lines['自分のボケです'] ?? null);
        $this->assertContains('このボケ好き', $lines);
        // 「俺の回答」になるのは「あなたの回答」の印が付いた回答だけ
        $this->assertSame(substr_count($html, 'c-answers__mine'), count(array_keys($lines, '俺の回答', true)));
    }

    public function testAnsweredPageSharesAsMine(): void
    {
        $this->post('/o/k7Qm2xPz/answers', ['body' => '完了画面のボケ'])->assertRedirect();
        $done = $this->withSession($_SESSION)->get('/o/k7Qm2xPz/done');
        $this->assertSame(['完了画面のボケ' => '俺の回答'], $this->answerShareLines((string) $done->getBody()));
    }

    public function testResultPageSharesAsThirdPerson(): void
    {
        $lines = $this->answerShareLines((string) $this->get('/o/q8Lr4nTb')->getBody());
        $this->assertNotEmpty($lines);
        $this->assertNotContains('俺の回答', $lines);
    }

    #[DataProvider('provideUnavailableAnswerIs404')]
    public function testUnavailableAnswerIs404(string $url): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get($url);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUnavailableAnswerIs404(): iterable
    {
        yield '存在しない回答' => ['/o/k7Qm2xPz/a/999999'];

        yield '別の大喜利の回答' => ['/o/Ee4fGg5h/a/90412'];

        yield '非表示の大喜利' => ['/o/Wq8nRt2v/a/1'];

        yield '削除済みの大喜利' => ['/o/Ty9pQw1z/a/1'];

        yield '存在しない大喜利' => ['/o/zzzzzzzz/a/90412'];
    }

    public function testHiddenAnswerIs404(): void
    {
        $this->db->table('answers')->where('id', 90412)->update(['status' => 'hidden']);
        $this->expectException(PageNotFoundException::class);
        $this->get('/o/k7Qm2xPz/a/90412');
    }

    public function testAnswerLinkOpensForAnotherBrowserAndReload(): void
    {
        $this->post('/o/k7Qm2xPz/answers', ['body' => 'リンクで開くボケ'])->assertRedirect();
        $id                = (int) $this->db->table('answers')->where('body', 'リンクで開くボケ')->get()->getRow()->id;
        $_COOKIE['bk_bid'] = 'another-browser';

        foreach ([1, 2] as $_) {
            $result = $this->get('/o/k7Qm2xPz/a/' . $id);
            $result->assertOK();
            $result->assertSee('リンクで開くボケ');
        }
    }
}
