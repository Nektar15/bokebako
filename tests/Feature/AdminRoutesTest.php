<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * 管理画面（A-02〜A-10）：各URLが期待する画面を返すこと（CODING_RULES 13章）
 *
 * testing 環境は development と同じく仮管理者（上位管理者）で通る。
 *
 * @internal
 */
final class AdminRoutesTest extends CIUnitTestCase
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
            'A-02 ダッシュボード'           => ['admin', '回答者 → 開催者 転換率'],
            'A-02 期間切り替え'             => ['admin?period=7d', '開催数（日別・直近14日）'],
            'A-03 一覧'                     => ['admin/oogiri', 'こんなコンビニ店員は嫌だ'],
            'A-03 一覧（状態・並び替え）'   => ['admin/oogiri?status=decided&sort=pv', '絶対に売れない駄菓子の商品名とは？'],
            'A-03 一覧（検索）'             => ['admin/oogiri?q=運動会', '100年後の運動会あるある'],
            'A-03 詳細'                     => ['admin/oogiri/12452', 'ベスト回答'],
            'A-03 詳細（代理変更フォーム）' => ['admin/oogiri/12452?edit=1', '順位を保存する'],
            'A-04 一覧'                     => ['admin/answers', '回答管理'],
            'A-04 一覧（絞り込み）'         => ['admin/answers?status=hidden&reports=1', 'LINEのIDはこちら→'],
            'A-05 待ち行列'                 => ['admin/moderation', 'AI補助判定'],
            'A-05 絞り込み'                 => ['admin/moderation?filter=abnormal', '同一環境から30秒間に12件'],
            'A-05 選択'                     => ['admin/moderation?item=oogiri-12471', 'うちの担任の嫌なところ選手権'],
            'A-06 一覧'                     => ['admin/ng-words', 'ワードを追加'],
            'A-06 編集'                     => ['admin/ng-words?edit=4', 'ワードを編集'],
            'A-07 一覧'                     => ['admin/ads', '広告を登録'],
            'A-07 編集'                     => ['admin/ads?edit=3', '広告を編集'],
            'A-08 OGP'                      => ['admin/ogp', '個別に再生成する'],
            'A-09 一覧'                     => ['admin/inquiries', 'お問い合わせ管理'],
            'A-09 絞り込みと選択'           => ['admin/inquiries?status=done&item=14', '合言葉を忘れてしまいました'],
            'A-10 運用設定'                 => ['admin/settings', 'お題の最大文字数'],
        ];
    }

    public function testStatusTabCountsAreShown(): void
    {
        $result = $this->get('admin/oogiri');
        $result->assertOK();
        $result->assertSee('12,480');
    }

    public function testUnknownOogiriIs404(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('admin/oogiri/99');
    }

    public function testNgWordExportIsCsv(): void
    {
        // ダウンロード応答は本文を直接返さないので、ステータスとヘッダーだけを見る
        $result = $this->get('admin/ng-words/export');
        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertInstanceOf(DownloadResponse::class, $result->response());
    }
}
