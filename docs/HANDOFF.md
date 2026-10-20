# 引き継ぎメモ（新しいセッション・別の開発者向け）

最終更新：2026-10-20　ブランチ：`develop`（工程0〜5まで取り込み済み）

## 今どこまでできているか

| 工程 | 状態 | ブランチ |
|---|---|---|
| 0 土台 | 完了・develop に取り込み済み | `feature/setup` |
| 1 ユーザー向け画面の外側（U-01〜U-18） | 完了・develop に取り込み済み | `feature/front-views` |
| 2 管理画面の外側（A-02〜A-10） | 完了・develop に取り込み済み | `feature/admin-views` |
| 3 DB とモデル／4 中身 | 完了・develop に取り込み済み | `feature/database` |
| 5 仕上げ | 完了・develop に取り込み済み（`composer check` 通過、165 テスト） | `feature/release-prep` |
| A-01 ログイン・2FA | **未実装**（下の「残課題」） | |

## 最初にやること（新しいセッションでそのまま貼る指示）

```
docs/PROMPT.md と docs/HANDOFF.md を読んでから進めてください。
まず develop で `cp .env.example .env && composer install && composer check` を通してください。
次は「残課題」の A-01（管理者ログイン・二段階認証）を feature/admin-login で実装してください。
```

### 開発ツールの入れ方（packagist に接続できない環境向け）

工程1〜5の作業環境では packagist.org に接続できなかった。`composer install` が通らないときは、
GitHub Releases の phar（phpstan 1.12、php-cs-fixer 3.95）と、PHPUnit 10.5 とその依存を GitHub から clone して
`vendor/bin/{phpstan,php-cs-fixer,phpunit}` に置く薄いラッパーで `composer check` を通した。
普通の環境では `composer install` だけでよい（`composer.lock` はまだない。初回の `composer install` で作ってコミットすること：CODING_RULES 1章）。

## 残課題（本番公開前）

### 1. A-01 管理者ログイン・二段階認証（未実装）

- 画面：ログイン（ID・パスワード・TOTP）、ログアウト、管理者アカウント管理（未確定事項10：画面定義がない）
- テーブル `admins` は作ってある（`login_id`・`password_hash`（bcrypt）・`role`・`totp_secret`・`failed_count`・`locked_until`）。使っているコードはまだない
- **実装時に消す仮認証コード**（CODING_RULES 7-1）：
  - `app/Filters/AdminAuth.php`：`DEV_ADMIN_*` 定数、development/testing で仮管理者を通す分岐、`?role=general`（ADR 0003-3）
  - `app/Views/partials/admin_sidebar.php`：「開発用の仮ログイン」の表示、「ログアウトは A-01 実装後に有効」
  - `tests/Unit/AdminAuthTest.php`・`tests/Feature/AdminRoleTest.php`：仮認証前提のテスト。本認証のログイン済みセッションに書き換える
  - `docs/admin-manual.md` 冒頭の「ログインは未実装」の注記
- `CurrentAdminService` は認証方法が変わっても使う（ここから先の権限判定は変えない）

### 2. 仮決めの確認（発注者）

ADR に【仮】と書いたもの。決まったら ADR の状態を更新し、`docs/FEATURES.md` 6章から消す。

| 項目 | 仮決め | ADR |
|---|---|---|
| 生IPの保存期間 | 保存しない（ハッシュのみ） | 0004-2 |
| 合言葉 | 任意、4文字以上 | 0004-8 |
| CAPTCHA | Cloudflare Turnstile | 0004-9 |
| 通報の自動非表示 | 3件／60分（運用設定で変更可） | 0004-12 |
| AI「非表示」判定の自動反映 | 既定 OFF（運用設定 `ai_auto_hide`） | 0004-15 |
| NGワードのカテゴリー | 固定7種 | 0003-6 |
| 広告の掲載場所 | 1広告1か所 | 0003-7 |
| 運用設定の追加3項目と上限・下限 | `SettingService::INT_RANGES` | 0003-8 |
| 通報数の強調の下限 | A-03：5件、A-04：3件 | 0003-9 |
| U-15・U-16 の本文 | ひな形（発注者支給の本文に差し替える。本番以外では「ひな形」の注記が出る） | — |
| U-05・U-11〜U-13 の文言、お題テンプレート | コード内の固定（未確定事項9） | — |

### 3. 本番の準備（README「Xserver へ置く」のチェックリスト）

- `.env`（`app.hashSecret` は一度決めたら変えない）、php.ini（OPcache）、cron 3本、フォント（`writable/fonts/`）、Turnstile・OpenAI のキー、通知メールの宛先
- ステージングで `tests/load/k6.js` を流し、`docs/load-test.md` に追記
- Cloudflare（任意）

### 4. 小さな積み残し

- メールの文面（`MailService`）は件名と本文の素朴な組み立て。テンプレート化は要望があれば
- OGP の背景・ロゴの差し替え（A-08）は PNG のみ。差し替え後の一括再生成ボタンはない（`php spark ogp:generate` は「欠けている画像」だけ作る）
- `events` の削除は 90 日固定（`StatsService::EVENT_KEEP_DAYS`）。運用設定にはしていない
- Google Fonts の見た目は作業環境の都合で未確認（工程1から同じ）
- Vercel に仮置きする場合の差分（vercel-php、`writable/` を `/tmp` へ、外部 DB、cron の代替）は未着手。本番の Xserver 向けコードは変えず、`vercel.json` と環境変数で吸収する方針

## 工程5で作ったもの

- セキュリティ：`app/Filters/SecurityHeaders.php`（HSTS・CSP・Permissions-Policy。許可先は `Config\App::$cspSources`）、`app/Filters/AjaxOnly.php`（JSON API の受け付け条件。ADR 0005-1）、`public/.htaccess`（HTTPS 転送・静的資産の長期キャッシュ・gzip）
- 性能：静的ページのページキャッシュ、`events` の 90 日削除、k6 スクリプト `tests/load/k6.js`
- 文書：`docs/load-test.md`、`docs/admin-manual.md`、`docs/db.md`、`docs/adr/0005`、README の運用章（本番 `.env`、php.ini、Cloudflare、バックアップ、リリース前チェック）
- テスト：`tests/Feature/SecurityTest.php`

## 工程5で直した不具合

- ページキャッシュで配信される結果ページから通報すると 403 になっていた（HTML に埋めた CSRF トークンが別の訪問者のもの）。API を `X-Requested-With` 方式に変更（ADR 0005-1。テストあり）

## 手で確認済みのこと（工程5）

- `CI_ENVIRONMENT=production` で起動し、CSP・HSTS 相当のヘッダー、管理画面 403、`app.hashSecret` 未設定時の起動エラーを確認
- Playwright（Chromium）で主要 10 画面を開き、CSP 違反・JS エラーがないこと。結果ページ（キャッシュ配信）から通報 API が通ること
- k6：各 20 req/s × 30 秒で p95 14 ms、エラー 0（`docs/load-test.md`）

## 工程ごとの判断の記録

- `docs/adr/0003`：工程2（管理画面の外側）
- `docs/adr/0004`：工程3・4（DB・Service）。直した不具合4件も「工程4」の節に
- `docs/adr/0005`：工程5（仕上げ）

## 環境のメモ

- フレームワーク本体は `system/` にコミット済み（ADR 0001）。本番に Composer は不要
- `.env` は `.env.example` から作る。開発では `app.hashSecret` が空でも動く（production では必須）
- 開発用データ：`php spark migrate` → `php spark db:seed DevSeeder`。開催者ページは Cookie `bk_host_{slug}=dev-token-{slug}` か合言葉「ぼけばこ」
- 管理画面の切替：絞り込みと選択はすべてクエリ（`?status=` `?filter=` `?item=` `?edit=` `?period=`）。`?role=general` で一般管理者（A-01 実装時に削除）
- テスト DB は SQLite `:memory:`（`phpunit.xml.dist`）。MariaDB で流すときはそこを書き換える
