<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\DevSeeder;
use App\Services\IdentityService;
use Tests\Support\DatabaseTestCase;

/**
 * 送信系：開催・回答・問い合わせ・開催者操作が、正しく遷移し、保存され、入力エラーを返すこと
 *
 * @internal
 */
final class FrontFormsTest extends DatabaseTestCase
{
    public function testCreateOogiriRedirectsToCreatedAndStores(): void
    {
        $result = $this->post('/oogiri', ['odai' => 'こんなコンビニ店員は嫌だ', 'passphrase' => 'ひみつのことば']);
        $result->assertRedirect();
        $this->assertMatchesRegularExpression('#/o/[A-Za-z0-9]{8}/created$#', $result->getRedirectUrl() ?? '');
        $this->seeInDatabase('oogiris', ['odai' => 'こんなコンビニ店員は嫌だ', 'status' => 'open']);
        $this->seeInDatabase('events', ['name' => 'create']);
        // 開催者トークンの Cookie がリダイレクトと一緒に返ること（これがないと開催者ページに入れない）
        $slug = basename(dirname((string) $result->getRedirectUrl()));
        $this->assertTrue($result->response()->hasCookie(IdentityService::HOST_COOKIE . '_' . $slug));
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

    public function testCreateOogiriWithRejectWordIsRefused(): void
    {
        $this->post('/oogiri', ['odai' => '全員殺すお題'])->assertSessionHas('errors', ['odai' => '投稿できない言葉が含まれています']);
        $this->dontSeeInDatabase('oogiris', ['odai' => '全員殺すお題']);
    }

    public function testCreateOogiriWithReviewWordIsAcceptedAndFlagged(): void
    {
        $this->post('/oogiri', ['odai' => 'LINEで言われたくない一言'])->assertRedirect();
        $this->seeInDatabase('oogiris', ['odai' => 'LINEで言われたくない一言', 'status' => 'open']);
        $this->seeInDatabase('ai_judgements', ['target_type' => 'oogiri', 'trigger' => 'ng_review']);
    }

    public function testCreateFromParentCountsDerived(): void
    {
        $this->post('/oogiri', ['odai' => '派生のお題', 'from' => 'k7Qm2xPz'])->assertRedirect();
        $this->seeInDatabase('oogiris', ['id' => 12480, 'derived_count' => 3]);
        $this->seeInDatabase('oogiris', ['odai' => '派生のお題', 'parent_oogiri_id' => 12480]);
    }

    public function testAnswerRedirectsToDoneAndCountsUp(): void
    {
        $this->post('/o/k7Qm2xPz/answers', ['body' => 'ボケ'])->assertRedirectTo(site_url('o/k7Qm2xPz/done'));
        $this->seeInDatabase('answers', ['oogiri_id' => 12480, 'body' => 'ボケ', 'status' => 'visible']);
        $this->seeInDatabase('oogiris', ['id' => 12480, 'answer_count' => 10]);
    }

    public function testAnswerAfterCloseIsRejected(): void
    {
        $this->post('/o/Zp3cW9aQ/answers', ['body' => 'ボケ'])->assertSessionHas('errors', ['_' => 'この大喜利の募集は終了しています']);
    }

    public function testAnswerBurstIsRateLimited(): void
    {
        // burst_count（DevSeeder: 3回/60秒）を超えると RATE_LIMITED
        for ($i = 0; $i < 3; $i++) {
            $this->post('/o/k7Qm2xPz/answers', ['body' => "ボケ{$i}"])->assertRedirectTo(site_url('o/k7Qm2xPz/done'));
        }
        $this->post('/o/k7Qm2xPz/answers', ['body' => 'ボケ4'])->assertSessionHas('errors', ['_' => '少し時間をおいてから投稿してください']);
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

    public function testContactSuccessStoresInquiry(): void
    {
        $this->post('/contact', ['type' => 'general', 'email' => 'a@example.com', 'body' => 'こんにちは'])->assertRedirectTo(site_url('contact/done'));
        $this->seeInDatabase('inquiries', ['email' => 'a@example.com', 'type' => 'general', 'status' => 'todo']);
    }

    public function testDecideWithoutPicksReturnsError(): void
    {
        $this->asHost('dev-token-Zp3cW9aQ')->post('/host/Zp3cW9aQ/result', [])->assertSessionHas('errors', ['ranks' => 'ベスト回答を1〜3件選んでください']);
    }

    public function testDecideWithTwoPicksStoresRanks(): void
    {
        // フォームの値は文字列で届く（invalidchars フィルタが文字列以外を受け付けない）
        $this->asHost('dev-token-Zp3cW9aQ')->post('/host/Zp3cW9aQ/result', ['ranks' => ['90322', '90341']])->assertRedirectTo(site_url('o/Zp3cW9aQ'));
        $this->seeInDatabase('oogiris', ['id' => 12466, 'status' => 'decided']);
        $this->seeInDatabase('answers', ['id' => 90322, 'rank' => 1]);
        $this->seeInDatabase('answers', ['id' => 90341, 'rank' => 2]);
    }

    public function testDecideWithoutTokenIsRefused(): void
    {
        $this->post('/host/Zp3cW9aQ/result', ['ranks' => ['90322']])->assertSessionHas('errors', ['_' => '開催者だけが操作できます']);
        $this->seeInDatabase('oogiris', ['id' => 12466, 'status' => 'awaiting_selection']);
    }

    public function testRecoverWithPassphraseSetsCookie(): void
    {
        $result = $this->post('/host/Zp3cW9aQ/recover', ['passphrase' => DevSeeder::PASSPHRASE]);
        $result->assertRedirectTo(site_url('host/Zp3cW9aQ'));
        $this->assertTrue($result->response()->hasCookie(IdentityService::HOST_COOKIE . '_Zp3cW9aQ'));
    }

    public function testRecoverWithWrongPassphrase(): void
    {
        $this->post('/host/Zp3cW9aQ/recover', ['passphrase' => 'ちがう'])->assertSessionHas('errors', ['passphrase' => '合言葉が違います']);
    }

    public function testFavoriteToggles(): void
    {
        $this->asHost()->post('/host/k7Qm2xPz/favorites/90412', [])->assertRedirect();
        $this->seeInDatabase('answers', ['id' => 90412, 'is_favorite' => 1]);
        $this->asHost()->post('/host/k7Qm2xPz/favorites/90412', [])->assertRedirect();
        $this->seeInDatabase('answers', ['id' => 90412, 'is_favorite' => 0]);
    }

    public function testHostDeleteMarksDeleted(): void
    {
        $this->asHost()->post('/host/k7Qm2xPz/delete', [])->assertRedirect();
        $this->seeInDatabase('oogiris', ['id' => 12480, 'status' => 'deleted']);
    }
}
