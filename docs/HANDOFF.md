# 引き継ぎメモ（新しいセッション・別の開発者向け）

最終更新：2026-10-20　ブランチ：`feature/admin-views`（工程2まで完了、develop 未マージ）

## 今どこまでできているか

| 工程 | 状態 | ブランチ |
|---|---|---|
| 0 土台 | 完了・develop に取り込み済み | `feature/setup` |
| 1 ユーザー向け画面の外側（U-01〜U-18） | 完了・develop に取り込み済み（`composer check` 通過） | `feature/front-views` |
| 2 管理画面の外側（A-02〜A-10） | 実装完了・`composer check` 通過。**develop 未マージ** | `feature/admin-views` |
| 3 DB とモデル | 未着手 | `feature/database`（これから） |
| 4 中身（Service 本実装） | 未着手。`// TODO(inner):` が 81 箇所 | |
| 5 仕上げ | 未着手 | |
| A-01 ログイン | 最終工程で実装。それまでは仮認証（CODING_RULES 7-1） | |

## 最初にやること（新しいセッションでそのまま貼る指示）

```
docs/PROMPT.md と docs/HANDOFF.md を読んでから進めてください。
まず feature/admin-views で `cp .env.example .env && composer install && composer check` を通し、
通ったら develop にマージして、工程3（DB とモデル）を feature/database で始めてください。
```

### 開発ツールの入れ方（packagist に接続できない環境向け）

工程1・2の作業環境では packagist.org に接続できなかった。`composer install` が通らないときは、
GitHub Releases の phar（phpstan 1.12、php-cs-fixer 3.95）と、PHPUnit 10.5 とその依存を GitHub から clone して
`vendor/bin/{phpstan,php-cs-fixer,phpunit}` に置く薄いラッパーで `composer check` を通した。
普通の環境では `composer install` だけでよい（`composer.lock` はまだない。初回の `composer install` で作ってコミットすること：CODING_RULES 1章）。

## 工程2で作ったもの

- View：`app/Views/admin/a02_dashboard.php` 〜 `a10_settings.php`（1画面1ファイル、先頭に View 契約）
- 共通部品：`partials/admin_badge`（状態バッジ）、`admin_tabs`（絞り込みタブ）、`admin_pager`、`admin_history`（操作履歴）、`admin_action_form`（1ボタン＝1フォームの操作、確認ダイアログつき）、`admin_notice`（操作後の通知・入力エラー）、`admin_setting`（A-10 の1行）
- CSS：`css/components/admin-*.css`（button / panel / status / table / tabs / pager / form / history）、`css/pages/a02〜a10-*.css`
- JS：`components/autosubmit.js`（セレクトやファイル選択で即送信、JS なしの送信ボタンを隠す）、`pages/ng-words.js`、`pages/ogp.js`、`pages/settings.js`
- Controller：`app/Controllers/Admin/AdminController.php`（共通の親：バッジ・通知・絞り込み値・404）と各画面の Controller。`BaseController::placeholder()` と `Views/placeholder.php` は削除した
- Service（Fixture を返す仮実装）：`StatsService`、`OogiriAdminService`、`AnswerAdminService`、`ModerationService`、`NgWordService`、`AdService`（管理側を追加）、`OgpService`、`InquiryService`、`SettingService`（全設定の読み出し・検証を追加）、`DisplayService`（管理画面用の整形を追加）
- Entity：`NgWord`、`Ad`、`Inquiry`、`ModerationItem`、`AdminLog`、`OgpJob`。`Oogiri`・`Answer` に管理画面用の集計プロパティを追加
- Enum：`NgWordMatchType`、`NgWordAction`、`AdStatus`、`AdSlot`、`InquiryStatus`、`InquiryType`（U-18 もこれを使うように変更）、`AiVerdict`、`ModerationFlag`、`ReportTarget`、`OgpKind`、`StatsPeriod`
- Fixture：`app/Services/Fixtures/Admin*Fixture.php`、`ModerationFixture`、`NgWordFixture`、`AdFixture`、`InquiryFixture`、`OgpFixture`、`StatsFixture`、`SettingFixture`
- テスト：`tests/Feature/AdminRoutesTest.php`（全画面の GET）、`AdminFormsTest.php`（全操作の POST と入力エラー）、`AdminRoleTest.php`（A-04 の権限による列の出し分け）

## 手で確認済みのこと（工程2）

- 14 パターンの URL（絞り込み・編集・選択・`?role=general` 込み）を Playwright（1280px）で描画。JS エラーなし、横スクロールなし
- JS の動作：A-06 一致方式の説明切替と「ためしに判定」、A-10 の未保存表示とスイッチの文言、A-03 の削除確認ダイアログ
- Google Fonts は作業環境の都合で読めなかったため、書体の見た目は未確認

## 仮置きした判断（発注者の確認待ち）

`docs/adr/0003-admin-views-decisions.md` と `docs/FEATURES.md` 6章「未確定事項」11〜13 が正。要点：

1. A-05 の操作ルートのキーを `{oogiri|answer}-{id}` に変更（対象が2種類あるため）
2. 仮認証に `?role=general`（一般管理者で表示確認。A-01 実装時に削除）
3. NGワードのカテゴリーは固定、広告の掲載場所は1つ、運用設定に仕様書にない3項目（制限方法・停止時間・急増の通知）を追加

## 工程3を始める人へ

- `SPEC.md` 3.3 の8テーブルに加えて、工程2で形を決めた `admin_logs`（操作履歴：`Entities/AdminLog`）、`ogp_jobs`（`Entities/OgpJob`）、集計テーブル（S-06。`StatsFixture::summary()` の項目）が必要
- Entity の `@property` と Fixture の配列キーがそのままカラム名の候補。Fixture は Seeder に移す（`TODO(inner)` を検索）
- `ModerationItem` はテーブルの行ではない（ADR 0003）。`reports`・NG検知・AI判定（`ai_verdict` / `ai_reason`）をどのテーブルに持つかはここで決める
- 部品（`partials/admin_*`）は値を省略できない（呼び出し間で値が残る）。View 契約のとおり毎回すべて渡す

## 環境のメモ

- フレームワーク本体は `system/` にコミット済み（ADR 0001）。本番に Composer は不要
- `.env` は `.env.example` から作る。`app.hashSecret` と DB 接続を入れる
- ダミー状態の切替（ユーザー向け）：`?fixture=open|open_empty|awaiting|decided|decided_one|no_result|hidden|hidden_fixable|deleted|recover|not_answered`、入力エラーは `?fixture=error_ngword|error_ratelimit|error_passphrase`、広告の見本は `?ads=1`（development 限定）
- 管理画面の切替：絞り込みと選択はすべてクエリ（`?status=` `?filter=` `?item=` `?edit=` `?period=`）。`?role=general` で一般管理者
