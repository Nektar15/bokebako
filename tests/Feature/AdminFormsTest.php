<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Filters;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * 管理画面の送信系（A-03〜A-10 の操作）：正しく戻り、入力エラーを返すこと
 *
 * 外側フェーズなので保存はしない。Controller → Service の呼び出しと遷移先だけを確認する。
 *
 * @internal
 */
final class AdminFormsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // FeatureTestTrait は CSRF フィルタも通すので、このテストではトークン検証を外して POST する
        $filters = config(Filters::class);
        unset($filters->globals['before']['csrf']);
    }

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
            'A-06 追加'             => ['admin/ng-words', ['word' => '住所', 'match_type' => 'partial', 'category' => '個人情報', 'action' => 'review', 'enabled' => '1']],
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

    public function testDecideWithoutPicksReturnsError(): void
    {
        $result = $this->post('admin/oogiri/12452/result', ['ranks' => ['0', '0', '0']]);
        $result->assertRedirect();
        $result->assertSessionHas('errors', ['ranks' => 'ベスト回答を1〜3件選んでください']);
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

    public function testSettingsSaveRedirectsWithNotice(): void
    {
        $result = $this->post('admin/settings', [
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
        ]);
        $result->assertRedirectTo(site_url('admin/settings'));
        $result->assertSessionHas('notice', '設定を保存しました。次のアクセスから反映されます');
    }
}
