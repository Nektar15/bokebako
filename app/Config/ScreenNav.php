<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * 画面一覧ナビ（確認用。ADR 0009）
 *
 * 画面の右下のボタンからモーダルを開き、ぼけばこの全画面へ1回で移動できるようにする。開発用データ（DevSeeder）の大喜利を使う。
 * 本番では使わない。.env の screenNav.enabled = true のときだけ表示し、専用のルート（/_screens/...）も有効になる。
 *
 * items：[グループ名 => [[画面ID・名前, 行き先, 補足], ...]]。行き先は次のどれか
 *   - 'url:/path'        そのまま開く
 *   - 'admin:/path'      管理画面のパス（app.adminPath の下）
 *   - 'host:{slug}:/path' 開催者としてログインしてから開く（DevSeeder の開催者トークン）
 *   - 'answerer:{slug}:/path' 回答したブラウザとして開く（DevSeeder の回答 A-90412 を投稿したブラウザ）
 */
class ScreenNav extends BaseConfig
{
    public bool $enabled = false;

    /**
     * @var array<string, list<array{0: string, 1: string, 2: string}>>
     */
    public array $items = [
        'ユーザー向け（開催する）' => [
            ['U-01 トップ・開催', 'url:/', ''],
            ['U-02 開催の確認', 'url:/', 'トップで「24時間大喜利を開催する」を押すと開く'],
            ['U-01 派生開催（?from=）', 'url:/?from=q8Lr4nTb', '結果ページの「自分も開催する」から来た状態'],
            ['U-03 開催完了', 'host:k7Qm2xPz:/o/k7Qm2xPz/created', '開催者として開く'],
        ],
        'ユーザー向け（回答する）' => [
            ['U-04 回答（募集中）', 'url:/o/k7Qm2xPz', ''],
            ['U-04 回答（まだ回答0件）', 'url:/o/Ee4fGg5h', ''],
            ['U-05 回答の確認', 'url:/o/k7Qm2xPz', '回答画面で「回答する」を押すと開く'],
            ['U-06 回答完了', 'answerer:k7Qm2xPz:/o/k7Qm2xPz/done', '回答したブラウザとして開く'],
            ['U-07 回答一覧', 'answerer:k7Qm2xPz:/o/k7Qm2xPz/answers', '回答したブラウザとして開く'],
            ['回答シェア用 URL', 'url:/o/k7Qm2xPz/a/90412', ''],
        ],
        'ユーザー向け（開催者）' => [
            ['U-08 開催者（募集中）', 'host:k7Qm2xPz:/host/k7Qm2xPz', '開催者として開く'],
            ['U-08 開催者（結果選択待ち）', 'host:Zp3cW9aQ:/host/Zp3cW9aQ', '開催者として開く'],
            ['U-09 結果の確認', 'host:Mn6bXs4e:/host/Mn6bXs4e', '回答を選んで「結果を発表する」を押すと開く'],
            ['U-08 合言葉の入力', 'url:/host/Hd3kLp9a', '開催者でないブラウザ。合言葉は「ぼけばこ」'],
        ],
        'ユーザー向け（24時間たったあと）' => [
            ['U-11 募集終了（結果選択待ち）', 'url:/o/Zp3cW9aQ', ''],
            ['U-10 結果発表', 'url:/o/q8Lr4nTb', ''],
            ['U-10 結果発表（別の例）', 'url:/o/Cv5tYu7i', ''],
            ['U-12 結果未選出', 'url:/o/Bx2gHj8k', ''],
            ['U-13 非表示', 'url:/o/Wq8nRt2v', ''],
            ['U-13 削除済み', 'url:/o/Ty9pQw1z', ''],
        ],
        'ユーザー向け（そのほか）' => [
            ['U-14 ぼけばことは', 'url:/about', ''],
            ['U-15 利用規約', 'url:/terms', ''],
            ['U-16 プライバシーポリシー', 'url:/privacy', ''],
            ['U-17 投稿ガイドライン', 'url:/guidelines', ''],
            ['U-18 お問い合わせ', 'url:/contact', ''],
            ['U-18 お問い合わせ完了', 'url:/contact/done', ''],
        ],
        '管理画面' => [
            ['A-01 ログイン', 'admin:/login', ''],
            ['A-01 管理者アカウント', 'admin:/accounts', 'ログインが要る'],
            ['A-02 ダッシュボード', 'admin:/', 'ログインが要る'],
            ['A-03 大喜利管理', 'admin:/oogiri', 'ログインが要る'],
            ['A-03 大喜利の詳細', 'admin:/oogiri/12452', 'ログインが要る'],
            ['A-04 回答管理', 'admin:/answers', 'ログインが要る'],
            ['A-05 通報・モデレーション', 'admin:/moderation', 'ログインが要る'],
            ['A-06 NGワード', 'admin:/ng-words', 'ログインが要る'],
            ['A-07 広告', 'admin:/ads', 'ログインが要る'],
            ['A-08 OGP', 'admin:/ogp', 'ログインが要る'],
            ['A-09 お問い合わせ管理', 'admin:/inquiries', 'ログインが要る'],
            ['A-10 運用設定', 'admin:/settings', 'ログインが要る'],
        ],
    ];
}
