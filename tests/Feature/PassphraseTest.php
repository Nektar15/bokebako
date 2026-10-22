<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Libraries\Passphrase;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

/**
 * 合言葉（BUG-002・BUG-006、ADR 0015）：必須・文字数・整え方を開催時と本人確認時でそろえる
 *
 * @internal
 */
final class PassphraseTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private function create(string $passphrase): string
    {
        $result = $this->post('/oogiri', ['odai' => '合言葉のテスト', 'passphrase' => $passphrase]);
        $this->assertMatchesRegularExpression('#/o/[A-Za-z0-9]{8}/created$#', (string) $result->getRedirectUrl(), json_encode($_SESSION['errors'] ?? null, JSON_UNESCAPED_UNICODE));

        return basename(dirname((string) $result->getRedirectUrl()));
    }

    public function testPassphraseIsRequired(): void
    {
        $this->post('/oogiri', ['odai' => '合言葉なし'])->assertSessionHas('errors', ['passphrase' => '合言葉を入力してください']);
        $this->post('/oogiri', ['odai' => '合言葉なし', 'passphrase' => "\u{3000}\u{200B} "])->assertSessionHas('errors', ['passphrase' => '合言葉を入力してください']);
        $this->dontSeeInDatabase('oogiris', ['odai' => '合言葉なし']);
    }

    #[DataProvider('providePassphraseLengthBoundaries')]
    public function testPassphraseLengthBoundaries(string $passphrase, bool $accepted): void
    {
        $result = $this->post('/oogiri', ['odai' => '文字数の境界', 'passphrase' => $passphrase]);

        if ($accepted) {
            $this->seeInDatabase('oogiris', ['odai' => '文字数の境界']);
        } else {
            $result->assertSessionHas('errors', ['passphrase' => '合言葉は4〜32文字で入力してください']);
            $this->dontSeeInDatabase('oogiris', ['odai' => '文字数の境界']);
        }
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function providePassphraseLengthBoundaries(): iterable
    {
        yield '3文字' => ['abc', false];

        yield '4文字' => ['abcd', true];

        yield '32文字' => [str_repeat('あ', 32), true];

        yield '33文字' => [str_repeat('あ', 33), false];
    }

    public function testErrorIsShownOnTopPage(): void
    {
        // 前は U-01 に合言葉のエラーを出す場所がなく、何も起きずにトップへ戻ったように見えた（BUG-002）
        $page = $this->withSession(['errors' => ['passphrase' => '合言葉は4〜32文字で入力してください'], '__ci_vars' => ['errors' => 'new']])->get('/');
        $page->assertSee('合言葉は4〜32文字で入力してください');
        $this->assertStringContainsString('id="passphrase-error"', (string) $page->getBody());
    }

    #[DataProvider('provideRecoverAcceptsSamePassphraseAfterNormalizing')]
    public function testRecoverAcceptsSamePassphraseAfterNormalizing(string $atCreate, string $atRecover): void
    {
        $slug = $this->create($atCreate);
        $this->post('/host/' . $slug . '/recover', ['passphrase' => $atRecover])->assertRedirectTo(site_url('host/' . $slug));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRecoverAcceptsSamePassphraseAfterNormalizing(): iterable
    {
        yield '前後の全角スペース' => ["\u{3000}ぼけ合言葉\u{3000}", 'ぼけ合言葉'];

        yield '全角英数と半角英数' => ['ＡＢＣ１２３', 'ABC123'];

        yield '見えない文字' => ["ひみ\u{200B}つの言葉", 'ひみつの言葉'];

        yield '記号と空白を含む' => ['a b!@#$%', 'a b!@#$%'];

        yield '32文字の日本語（72バイトを超える）' => [str_repeat('あ', 31) . 'い', str_repeat('あ', 31) . 'い'];
    }

    public function testLongPassphraseIsNotTruncated(): void
    {
        // bcrypt は 72 バイトまでしか見ないので、末尾だけ違う日本語の合言葉が通っていた
        $slug = $this->create(str_repeat('あ', 31) . 'い');
        $this->post('/host/' . $slug . '/recover', ['passphrase' => str_repeat('あ', 31) . 'う'])->assertSessionHas('errors', ['passphrase' => '合言葉が違います']);
    }

    public function testOldHashFormatStillWorks(): void
    {
        // 2026-10-22 より前に作った大喜利（Text::clean() だけで整えた password_hash）
        $this->db->table('oogiris')->where('slug', 'Zp3cW9aQ')->update(['passphrase_hash' => password_hash('むかしの言葉', PASSWORD_DEFAULT)]);
        $this->post('/host/Zp3cW9aQ/recover', ['passphrase' => "\u{3000}むかしの言葉"])->assertRedirectTo(site_url('host/Zp3cW9aQ'));
    }

    public function testStoredHashIsNotPlainText(): void
    {
        $slug = $this->create('ひみつのことば');
        $row  = $this->db->table('oogiris')->where('slug', $slug)->get()->getRowArray();
        $this->assertStringNotContainsString('ひみつのことば', (string) $row['passphrase_hash']);
        $this->assertTrue(Passphrase::verify('ひみつのことば', (string) $row['passphrase_hash']));
    }
}
