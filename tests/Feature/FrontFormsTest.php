<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Filters;

/**
 * 送信系：開催・回答・問い合わせ・開催者操作が、正しく遷移し、入力エラーを返すこと
 *
 * @internal
 */
final class FrontFormsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // FeatureTestTrait は CSRF フィルタも通すので、このテストではトークン検証を外して POST する
        // （Factories は setUp ごとにリセットされるため、他のテストには影響しない）
        $filters = config(Filters::class);
        unset($filters->globals['before']['csrf']);
    }

    public function testCreateOogiriRedirectsToCreated(): void
    {
        $this->post('/oogiri', ['odai' => 'こんなコンビニ店員は嫌だ'])->assertRedirectTo(site_url('o/k7Qm2xPz/created'));
    }

    public function testCreateOogiriWithEmptyOdaiReturnsError(): void
    {
        $result = $this->post('/oogiri', ['odai' => '']);
        $result->assertRedirect();
        $result->assertSessionHas('errors', ['odai' => 'お題を入力してください']);
    }

    public function testCreateOogiriTooLong(): void
    {
        $this->post('/oogiri', ['odai' => str_repeat('あ', 101)])->assertSessionHas('errors', ['odai' => 'お題は100文字以内で入力してください']);
    }

    public function testAnswerRedirectsToDone(): void
    {
        $this->post('/o/k7Qm2xPz/answers', ['body' => 'ボケ'])->assertRedirectTo(site_url('o/k7Qm2xPz/done'));
    }

    public function testAnswerAfterCloseIsRejected(): void
    {
        $this->post('/o/k7Qm2xPz/answers?fixture=awaiting', ['body' => 'ボケ'])->assertSessionHas('errors', ['_' => 'この大喜利の募集は終了しています']);
    }

    public function testContactValidation(): void
    {
        $this->post('/contact', ['type' => 'delete', 'email' => 'bad', 'url' => '', 'body' => ''])
            ->assertSessionHas('errors', [
                'email' => 'メールアドレスを正しく入力してください',
                'url'   => '対象のURLを入力してください',
                'body'  => 'お問い合わせ内容を入力してください',
            ]);
    }

    public function testContactSuccess(): void
    {
        $this->post('/contact', ['type' => 'general', 'email' => 'a@example.com', 'body' => 'こんにちは'])->assertRedirectTo(site_url('contact/done'));
    }

    public function testDecideWithoutPicksReturnsError(): void
    {
        $this->post('/host/k7Qm2xPz/result?fixture=awaiting', [])->assertSessionHas('errors', ['ranks' => 'ベスト回答を1〜3件選んでください']);
    }

    public function testDecideWithTwoPicksRedirects(): void
    {
        // フォームの値は文字列で届く（invalidchars フィルタが文字列以外を受け付けない）
        $this->post('/host/k7Qm2xPz/result?fixture=awaiting', ['ranks' => ['90412', '90410']])->assertRedirect();
    }
}
