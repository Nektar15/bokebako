<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NgWordAction;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

/**
 * 管理画面の送信系（A-03〜A-10 の操作）：正しく戻り、保存され、入力エラーを返すこと
 *
 * @internal
 */
final class AdminFormsTest extends DatabaseTestCase
{
    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('provideActionRedirects')]
    public function testActionRedirects(string $path, array $body): void
    {
        $this->post($path, $body)->assertRedirect();
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function provideActionRedirects(): iterable
    {
        return [
            'A-03 非表示'           => ['admin/oogiri/12480/hide', []],
            'A-03 再公開'           => ['admin/oogiri/12471/publish', []],
            'A-03 削除'             => ['admin/oogiri/12377/delete', []],
            'A-03 代理変更'         => ['admin/oogiri/12452/result', ['ranks' => ['90412', '0', '0']]],
            'A-03 キャッシュ再生成' => ['admin/oogiri/12452/cache', []],
            'A-04 非表示'           => ['admin/answers/90412/hide', []],
            'A-04 再公開'           => ['admin/answers/90398/publish', []],
            'A-04 削除'             => ['admin/answers/90301/delete', []],
            'A-05 問題なし'         => ['admin/moderation/answer-90398/resolve', []],
            'A-05 非表示'           => ['admin/moderation/answer-90341/hide', []],
            'A-05 削除'             => ['admin/moderation/oogiri-12471/delete', []],
            'A-05 投稿元制限'       => ['admin/moderation/answer-90301/restrict', ['hours' => '24']],
            'A-06 追加'             => ['admin/ng-words', ['word' => '電話番号', 'match_type' => 'partial', 'category' => '個人情報', 'action' => 'review', 'enabled' => '1']],
            'A-06 編集（無効化）'   => ['admin/ng-words/4', ['word' => 'LINE', 'match_type' => 'partial', 'category' => '個人情報', 'action' => 'review', 'enabled' => '0']],
            'A-06 削除'             => ['admin/ng-words/8/delete', []],
            'A-07 登録'             => ['admin/ads', ['name' => '秋', 'sponsor' => 'まるや製菓', 'url' => 'https://example.com/', 'slot' => 'top', 'starts_at' => '2026-10-01T00:00', 'ends_at' => '2026-10-31T23:59', 'action' => 'publish']],
            'A-07 編集'             => ['admin/ads/1', ['name' => '秋', 'sponsor' => 'まるや製菓', 'url' => 'https://example.com/', 'slot' => 'bottom', 'starts_at' => '2026-10-01T00:00', 'ends_at' => '2026-10-31T23:59', 'action' => 'draft']],
            'A-07 削除'             => ['admin/ads/6/delete', []],
            'A-08 再生成'           => ['admin/ogp/regenerate', ['kind' => 'answer', 'target' => 'A-90412']],
            'A-09 対応状況'         => ['admin/inquiries/17', ['status' => 'doing', 'memo' => '']],
            'A-09 メモ'             => ['admin/inquiries/15', ['status' => 'doing', 'memo' => '返信済み']],
        ];
    }

    public function testHideOogiriStoresStatusAndLog(): void
    {
        $this->post('admin/oogiri/12480/hide', [])->assertRedirect();
        $this->seeInDatabase('oogiris', ['id' => 12480, 'status' => 'hidden', 'status_before_hide' => 'open']);
        $this->seeInDatabase('admin_logs', ['target_type' => 'oogiri', 'target_id' => 12480, 'what' => '非表示にした']);
    }

    public function testPublishHiddenOogiriRestoresStatus(): void
    {
        $this->post('admin/oogiri/12471/publish', [])->assertRedirect();
        $this->seeInDatabase('oogiris', ['id' => 12471, 'status_before_hide' => null]);
        $this->dontSeeInDatabase('oogiris', ['id' => 12471, 'status' => 'hidden']);
    }

    public function testHideAnswerDecrementsCount(): void
    {
        $this->post('admin/answers/90412/hide', [])->assertRedirect();
        $this->seeInDatabase('answers', ['id' => 90412, 'status' => 'hidden']);
        $this->seeInDatabase('oogiris', ['id' => 12480, 'answer_count' => 8]);
    }

    public function testModerationRestrictInsertsRestriction(): void
    {
        $this->post('admin/moderation/answer-90341/restrict', ['hours' => '24'])->assertRedirect();
        $this->seeInDatabase('restrictions', ['reason' => '管理者による制限（A-90341）']);
        $this->seeInDatabase('admin_logs', ['target_type' => 'answer', 'target_id' => 90341, 'what' => '投稿元を24時間制限']);
    }

    public function testNgWordIsStoredAndCacheCleared(): void
    {
        $this->post('admin/ng-words', ['word' => '住所を教えて', 'match_type' => 'partial', 'category' => '個人情報', 'action' => 'reject', 'enabled' => '1'])->assertRedirect();
        $this->seeInDatabase('ng_words', ['word' => '住所を教えて', 'action' => 'reject']);
        $this->assertSame(NgWordAction::Reject, service('ngWord')->check('住所を教えてください')['action']);
    }

    public function testInquiryStatusIsStored(): void
    {
        $this->post('admin/inquiries/17', ['status' => 'doing', 'memo' => '確認中'])->assertRedirect();
        $this->seeInDatabase('inquiries', ['id' => 17, 'status' => 'doing']);
        // 対応メモは admin_logs に積む（履歴として残すため）
        $this->seeInDatabase('admin_logs', ['target_type' => 'inquiry', 'target_id' => 17, 'what' => '確認中']);
    }

    public function testSettingsArePersisted(): void
    {
        $this->post('admin/settings', self::validSettings(['odai_max' => '120']))->assertRedirectTo(site_url('admin/settings'));
        $this->seeInDatabase('settings', ['name' => 'odai_max', 'value' => '120']);
        $this->assertSame(120, service('settings')->int('odai_max'));
    }

    public function testDecideWithoutPicksReturnsError(): void
    {
        $result = $this->post('admin/oogiri/12452/result', ['ranks' => ['0', '0', '0']]);
        $result->assertRedirect();
        $result->assertSessionHas('errors', ['ranks' => 'ベスト回答を1〜3件選んでください']);
    }

    public function testDuplicateNgWordReturnsError(): void
    {
        $result = $this->post('admin/ng-words', ['word' => '住所', 'match_type' => 'partial', 'category' => '個人情報', 'action' => 'review']);
        $result->assertRedirect();
        $result->assertSessionHas('errors', ['word' => '同じワード・一致方式がすでに登録されています']);
    }

    public function testNgWordWithBrokenRegexReturnsError(): void
    {
        $result = $this->post('admin/ng-words', ['word' => '(abc', 'match_type' => 'regex', 'category' => 'スパム', 'action' => 'reject']);
        $result->assertRedirect();
        $result->assertSessionHas('errors', ['word' => '正しい正規表現ではありません']);
    }

    public function testAdWithHttpUrlReturnsError(): void
    {
        $result = $this->post('admin/ads', ['name' => 'x', 'sponsor' => 'y', 'url' => 'http://example.com/', 'slot' => 'top', 'starts_at' => '2026-10-01T00:00', 'ends_at' => '2026-09-01T00:00']);
        $result->assertRedirect();
        $result->assertSessionHas('errors', [
            'url'    => 'リンク先URLを https:// から入力してください',
            'period' => '掲載終了は掲載開始より後にしてください',
        ]);
    }

    public function testOgpRegenerateWithoutTargetReturnsError(): void
    {
        $result = $this->post('admin/ogp/regenerate', ['kind' => 'recruit', 'target' => '']);
        $result->assertRedirect();
        $result->assertSessionHas('errors', ['target' => '大喜利のURL・スラッグ・IDを入力してください']);
    }

    public function testInquiryWithNothingToSaveReturnsError(): void
    {
        $result = $this->post('admin/inquiries/17', ['status' => '', 'memo' => '']);
        $result->assertRedirect();
        $result->assertSessionHas('errors', ['memo' => '対応メモを入力してください']);
    }

    public function testSettingsAreValidated(): void
    {
        $result = $this->post('admin/settings', [
            'odai_max'          => '0',
            'answer_max'        => '140',
            'open_hours'        => '24',
            'select_hours'      => '72',
            'burst_count'       => '3',
            'burst_window_sec'  => '60',
            'limit_method'      => 'temporary',
            'ban_minutes'       => '30',
            'report_count'      => '3',
            'report_window_min' => '60',
            'surge_count'       => '10',
            'ai_monthly_limit'  => '10000',
            'hashtag'           => '#ぼけばこ',
            'tpl_recruit'       => 'お題：{odai}',
            'tpl_answer'        => '{url}',
            'tpl_result'        => '{url}',
        ]);
        $result->assertRedirect();
        $result->assertSessionHas('errors', [
            'odai_max'    => '10〜500 の整数で入力してください',
            'hashtag'     => 'ハッシュタグを # なしで入力してください',
            'tpl_recruit' => '投稿文には {url} を含めてください',
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function validSettings(array $overrides = []): array
    {
        return $overrides + [
            'odai_max'          => '100',
            'answer_max'        => '140',
            'open_hours'        => '24',
            'select_hours'      => '72',
            'burst_count'       => '3',
            'burst_window_sec'  => '60',
            'limit_method'      => 'temporary',
            'ban_minutes'       => '30',
            'report_count'      => '3',
            'report_window_min' => '60',
            'surge_count'       => '10',
            'ai_enabled'        => '1',
            'ai_targets'        => ['ng_review', 'reported'],
            'ai_monthly_limit'  => '10000',
            'hashtag'           => 'ぼけばこ',
            'tpl_recruit'       => '{odai} {url}',
            'tpl_answer'        => '{answer} {url}',
            'tpl_result'        => '{first} {url}',
        ];
    }

    public function testSettingsSaveRedirectsWithNotice(): void
    {
        $result = $this->post('admin/settings', self::validSettings());
        $result->assertRedirectTo(site_url('admin/settings'));
        $result->assertSessionHas('notice', '設定を保存しました。次のアクセスから反映されます');
    }
}
