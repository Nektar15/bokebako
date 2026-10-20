# 引き継ぎメモ（新しいセッション・別の開発者向け）

最終更新：2026-10-20　ブランチ：`develop`（工程0〜5とレビュー修正を取り込み済み）＋ `feature/admin-login`（A-01 ログイン。develop へは未取り込み）

## 今どこまでできているか

| 工程 | 状態 | ブランチ |
|---|---|---|
| 0 土台 | 完了・develop に取り込み済み | `feature/setup` |
| 1 ユーザー向け画面の外側（U-01〜U-18） | 完了・develop に取り込み済み | `feature/front-views` |
| 2 管理画面の外側（A-02〜A-10） | 完了・develop に取り込み済み | `feature/admin-views` |
| 3 DB とモデル／4 中身 | 完了・develop に取り込み済み | `feature/database` |
| 5 仕上げ | 完了・develop に取り込み済み（`composer check` 通過、165 テスト） | `feature/release-prep` |
| レビュー修正（P1 5件・P2 2件） | 完了・develop に取り込み済み（178 テスト） | `fix/review-stage5` |
| A-01 ログイン・管理者アカウント | 完了・`composer check` 通過（201 テスト）。**develop へは未取り込み**。仮認証は削除済み | `feature/admin-login` |
| A-01 二段階認証（TOTP） | **未実装・最終工程**（下の「残課題」） | |

## 最初にやること（新しいセッションでそのまま貼る指示）

```
docs/PROMPT.md と docs/HANDOFF.md を読んでから進めてください。
まず develop で `cp .env.example .env && composer install && composer check` を通してください。
feature/admin-login を develop に取り込んでから、残課題の A-01 二段階認証（TOTP）を最終工程として実装してください（ADR 0007-6）。
```

### 開発ツールの入れ方（packagist に接続できない環境向け）

工程1〜5の作業環境では packagist.org に接続できなかった。`composer install` が通らないときは、
GitHub Releases の phar（phpstan 1.12、php-cs-fixer 3.95）と、PHPUnit 10.5 とその依存を GitHub から clone して
`vendor/bin/{phpstan,php-cs-fixer,phpunit}` に置く薄いラッパーで `composer check` を通した。
普通の環境では `composer install` だけでよい。`composer.lock` は 2026-10-20 に追加した（Mac・PHP 8.2.34 で作成。`config.platform.php = 8.2.0` で PHP 8.2 向けに解決する）。

**手元の PHP は 8.2 を使う**。PHP 8.5 で `composer check` を流すと、PHPStan 1.12 が非推奨の警告を大量に出してメモリ不足で止まる（コードの問題ではない）。Mac なら `brew install php@8.2` → `export PATH="/opt/homebrew/opt/php@8.2/bin:$PATH"`。PHPStan 2 へ上げるなら依存の変更なので ADR を書いて別ブランチで。

レビュー修正の作業環境（2026-10-20）も packagist に繋がらず、同じ方法を取った。手順の補足：
- PHPUnit 10.5.58 と依存（sebastian/*、phpunit/php-*、phar-io/*、theseer/tokenizer、myclabs/deep-copy、nikic/php-parser 5）、codeigniter/coding-standard 1.9.2、nexusphp/cs-config 3.29 を GitHub から `git clone`。リポジトリの外に置き、`classmap`／`psr-4`／`files` を並べた composer.json で `composer dump-autoload` する
- CodeIgniter の起動は `Composer\InstalledVersions` を使うので、composer の phar から `src/Composer/InstalledVersions.php` を取り出し、空の `vendor/composer/installed.php` と一緒に置く
- リポジトリの `vendor/autoload.php` はその autoload を読むだけ。`vendor/bin/php-cs-fixer` は `-d auto_prepend_file=` で同じ autoload を読ませる（設定ファイルが CodingStandard のクラスを使うため）

## 残課題（本番公開前）

### 1. A-01 二段階認証（TOTP。最終工程）

- ID・パスワードのログイン、試行回数制限、セッション、ログアウト、IP 制限、管理者アカウントは実装済み（ADR 0007）。仮認証のコードは削除した
- 足す場所：`LoginController::login()` で `AdminAuthService::verifyPassword()` が通ったあと、`startSession()` の前に TOTP の入力画面を挟む。`admins.totp_secret` は未使用のまま残してある
- 決めること：TOTP を必須にするか（全員／上位管理者だけ）、登録の流れ（QR コードの表示、確認コード）、端末をなくしたときの復旧（上位管理者が解除する／バックアップコード）、ライブラリを使うか自前で書くか（依存を増やすなら ADR）
- **二段階認証を入れる前に本番で管理画面を開けるか**は発注者と決める（今はパスワードだけで入れる）

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
| 管理者ログインの数値（5回失敗で15分ロック、IP 20回／10分、無操作120分、最長12時間、パスワード12文字以上） | `AdminAuthService`・`AdminAccountService` の定数 | 0007-2 |
| 管理者アカウントの画面（未確定事項10） | `/{admin}/accounts`（A-01 の一部） | 0007-4 |
| 通報数の強調の下限 | A-03：5件、A-04：3件 | 0003-9 |
| U-15・U-16 の本文 | ひな形（発注者支給の本文に差し替える。本番以外では「ひな形」の注記が出る） | — |
| U-05・U-11〜U-13 の文言、お題テンプレート | コード内の固定（未確定事項9） | — |

### 3. 本番の準備（README「Xserver へ置く」のチェックリスト）

- 最初の管理者を `php spark admin:create` で作る。`.env` に `cookie.secure = true`
- `.env`（`app.hashSecret` は一度決めたら変えない）、php.ini（OPcache）、cron 3本、フォント（`writable/fonts/`）、Turnstile・OpenAI のキー、通知メールの宛先
- ステージングで `tests/load/k6.js` を流し、`docs/load-test.md` に追記
- Cloudflare（任意）。使う場合、非表示・削除で消した OGP 画像（`/ogp/*`）は CDN のキャッシュが切れるまで配信される。`/ogp/*` のキャッシュ時間を短くするか、消したときに CDN のパージを足す（ADR 0006-7）

### 4. 小さな積み残し

- メールの文面（`MailService`）は件名と本文の素朴な組み立て。テンプレート化は要望があれば
- OGP の背景・ロゴの差し替え（A-08）は PNG のみ。差し替え後の一括再生成ボタンはない（`php spark ogp:generate` は「欠けている画像」だけ作る）
- `events` の削除は 90 日固定（`StatsService::EVENT_KEEP_DAYS`）。運用設定にはしていない
- Google Fonts の見た目は作業環境の都合で未確認（工程1から同じ）
- Vercel に仮置きする場合の差分（vercel-php、`writable/` を `/tmp` へ、外部 DB、cron の代替）は未着手。本番の Xserver 向けコードは変えず、`vercel.json` と環境変数で吸収する方針

## 工程5後のコードレビューで直したもの（`fix/review-stage5`。ADR 0006）

| 指摘 | 内容 | 直し方 | テスト |
|---|---|---|---|
| 1 P1 | 別の大喜利・非表示の回答IDでも確定できた | 全件が「この大喜利の公開中の回答」でなければ全体を拒否 | `HostServiceTest`・`OogiriAdminServiceTest` |
| 2 P1 | 共有キャッシュの結果ページに「あなたの回答」 | U-10・U-12 では付けない（U-07 だけ） | `ResultPageCacheTest` |
| 3 P1 | Cookie を変えると試行回数制限が戻った | IP 単位の枠を追加。合言葉は IP＋大喜利・IP・大喜利の3枠 | `RateLimitServiceTest`・`HostServiceTest` |
| 4 P1 | 同時の確定で先の結果が上書きされた | 読んだ状態を条件にした UPDATE（確定・S-01・S-02） | `HostServiceTest` |
| 5 P2 | 「もっと見る」が1ページ目のキャッシュを返した | キャッシュキーに `page` を含め、`?page=` 付きは保存しない | `ResultPageCacheTest` |
| 6 P2 | 締切後の再公開で選出期限が空のまま | S-01 と同じ選出期限を入れる | `OogiriAdminServiceTest` |
| 7 P1 | 非表示・削除後も OGP 画像が残った | 消す・作り直す。生成失敗時は古い画像を消す | `OogiriAdminServiceTest` |

あわせて、テストの土台でページキャッシュが `writable/cache/` に書かれ、テストや実行をまたいで古い HTML が返っていたのを直した（`DatabaseTestCase`）。

### レビューの「読みやすさ」で残したもの（今回は直していない）

- 状態遷移の集約：確定・S-01・S-02 は条件付き更新にそろえたが、非表示・再公開・削除（`OogiriAdminService`・`HostService::delete`）は条件なしのまま。1か所にまとめるなら別ブランチで
- Service の依存：コンストラクタ注入と `service('…')` の混在はそのまま（今回足したのは `OogiriAdminService` → `LifecycleService` の注入だけ）
- 保存と副作用の境界：回答保存と件数加算、通報保存と件数加算は別の更新のまま
- 取得上限：`HostService::answers()` は新着200件で打ち切り。201件目以降の回答を開催者が選べない。モデレーション一覧も先に200件に絞っている
- 確認できていないこと：MariaDB での同時実行（テストは SQLite で、同じ状態を読んだ2つの処理を順に流して確認）、HTTP サーバー越しの画面操作

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
- `docs/adr/0006`：工程5後のコードレビューの修正
- `docs/adr/0007`：A-01 ログインと管理者アカウント（二段階認証の前まで）

## 環境のメモ

- フレームワーク本体は `system/` にコミット済み（ADR 0001）。本番に Composer は不要
- `.env` は `.env.example` から作る。開発では `app.hashSecret` が空でも動く（production では必須）
- 開発用データ：`php spark migrate` → `php spark db:seed DevSeeder`。開催者ページは Cookie `bk_host_{slug}=dev-token-{slug}` か合言葉「ぼけばこ」
- 管理画面の切替：絞り込みと選択はすべてクエリ（`?status=` `?filter=` `?item=` `?edit=` `?period=`）
- 管理画面のログイン：DevSeeder の `dev-admin`（上位）／`dev-general`（一般）、パスワード `dev-password-bokebako`。テストでは `DatabaseTestCase::asAdmin()`
- テスト DB は SQLite `:memory:`（`phpunit.xml.dist`）。MariaDB で流すときはそこを書き換える
