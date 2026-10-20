# 引き継ぎメモ（新しいセッション・別の開発者向け）

最終更新：2026-10-20　ブランチ：`feature/database`（工程3・4まで完了、develop 未マージ）

## 今どこまでできているか

| 工程 | 状態 | ブランチ |
|---|---|---|
| 0 土台 | 完了・develop に取り込み済み | `feature/setup` |
| 1 ユーザー向け画面の外側（U-01〜U-18） | 完了・develop に取り込み済み | `feature/front-views` |
| 2 管理画面の外側（A-02〜A-10） | 完了・develop に取り込み済み | `feature/admin-views` |
| 3 DB とモデル | 完了（14テーブル・Model・Entity・DevSeeder） | `feature/database` |
| 4 中身（Service 本実装） | 完了・`composer check` 通過（160 テスト）。`TODO(inner)` は 0 箇所。**develop 未マージ** | `feature/database` |
| 5 仕上げ | 未着手 | `feature/release-prep`（これから） |
| A-01 ログイン | 最終工程で実装。それまでは仮認証（CODING_RULES 7-1） | |

## 最初にやること（新しいセッションでそのまま貼る指示）

```
docs/PROMPT.md と docs/HANDOFF.md を読んでから進めてください。
まず feature/database で `cp .env.example .env && composer install && composer check` を通し、
通ったら develop にマージして、工程5（仕上げ）を feature/release-prep で始めてください。
```

### 開発ツールの入れ方（packagist に接続できない環境向け）

工程1〜4の作業環境では packagist.org に接続できなかった。`composer install` が通らないときは、
GitHub Releases の phar（phpstan 1.12、php-cs-fixer 3.95）と、PHPUnit 10.5 とその依存を GitHub から clone して
`vendor/bin/{phpstan,php-cs-fixer,phpunit}` に置く薄いラッパーで `composer check` を通した。
普通の環境では `composer install` だけでよい（`composer.lock` はまだない。初回の `composer install` で作ってコミットすること：CODING_RULES 1章）。

## 工程3・4で作ったもの

- **DB**：`app/Database/Migrations/`（14テーブル）、`app/Models/`（テーブルごとに1つ。`BaseModel` に `increment()`／`decrement()` と JST 設定）、`app/Database/Seeds/DevSeeder.php`（全テーブルを入れ直す開発用データ。開催者トークンは `dev-token-{slug}`、合言葉「ぼけばこ」）
- **Service**（`app/Services/`、すべて `service('name')` で取る。登録は `app/Config/Services.php`）：
  `identity`（ハッシュ・トークン）、`rateLimit`（S-03）、`ngWord`、`oogiri`（開催）、`answer`（回答）、`host`（開催者の認証・確定）、`report`（通報）、`contact`、`ads`、`event`（計測の記録）、`stats`（集計・ダッシュボード）、`lifecycle`（S-01・S-02）、`moderation`（A-05・S-04・S-05）、`ogp`（S-07）、`pageCache`、`mail`、`adminLog`、`settings`、`oogiriAdmin`／`answerAdmin`／`inquiry`（管理画面）
- **Library**（`app/Libraries/`）：`OgpImage`（GD で 1200×630）、`OpenAiClient`（AI 判定）、`Captcha`（Turnstile）
- **コマンド**（`app/Commands/`）：`cron:minute`、`oogiri:close`、`oogiri:expire`、`reports:process`、`ai:judge`、`stats:daily`、`ogp:generate`
- **ルート追加**：`POST host/{slug}/restore`（localStorage のトークンで Cookie を復元）
- **View の変更**：CAPTCHA 部品 `partials/captcha`、U-10〜U-12 に `<meta name="bk:pv">`（JS が PV を送る）、`data-js-track` の値を `EventName` に揃えた
- **削除**：`app/Services/Fixtures/`（ダミーデータ）、`?fixture=` の切替
- **テスト**：`tests/_support/DatabaseTestCase.php`（SQLite `:memory:` にマイグレーションと DevSeeder を流す親クラス。`asHost()` で開催者として呼ぶ）。Feature は全画面の GET／全操作の POST と保存の確認、Unit は `LifecycleServiceTest`（S-01・S-02）、`HostServiceTest`（認証・確定 1〜3件・再確定不可）、`NgWordServiceTest`、`RateLimitServiceTest`

## 手で確認済みのこと（工程4）

- MariaDB 10.11 で `migrate:refresh` → `db:seed DevSeeder` → 開催 → 回答 → 開催者ページ → 確定 → 結果ページ → 通報 API → 管理画面の非表示・NGワード追加・投稿元制限・OGP 再生成、を curl と Playwright（Chromium）で通した
- cron の7コマンドを手で実行（`stats:daily` が前日分を集計し、ダッシュボードに出ることを確認）
- OGP 画像は Noto Sans CJK で募集・結果・回答の3種を生成し、目で確認
- Google Fonts は作業環境の都合で読めなかったため、書体の見た目は未確認（工程1から同じ）

## 工程4で直した不具合（レビュー時の参考）

- 開催直後に開催者ページへ入れなかった：`redirect()` は共有 Response の Cookie を持たないため、`withCookies()` を付けた（ADR 0004-21。テストあり）
- 運用設定が反映されなかった：`SettingService::int()` などが既定値を返していた。`all()` を通すようにした（テストあり）
- レート制限のキャッシュキーに `:` が入っていた（Redis・Memcached・テストの MockCache で例外）。`_` に変更
- NGワードの重複登録が DB エラー（UNIQUE）になっていた。入力エラーにした

## 仮置きした判断（発注者の確認待ち）

`docs/adr/0004-services-decisions.md` が正。要点：

1. 生IPは保存しない（ハッシュのみ）。合言葉は任意・4文字以上。CAPTCHA は Turnstile。通報の自動非表示は 3件／60分
2. A-05 の待ち行列は `ai_judgements` テーブル。AI の「非表示」判定は運用設定 `ai_auto_hide` が ON のときだけ自動
3. cron は `cron:minute` 1本 ＋ `stats:daily`（4:00）＋ `ogp:generate`
4. OGP のフォントはリポジトリに入れない（`writable/fonts/` に置く。README）

工程2の分は `docs/adr/0003-admin-views-decisions.md`。

## 工程5を始める人へ

- 100万PV 対策の確認：ページキャッシュの対象（U-10〜U-12 の1ページ目）、`events` の肥大化（`stats:daily` 後の古い行の削除は未実装）、インデックス（各マイグレーションの末尾）
- 負荷試験（k6）の結果を `docs/load-test.md` に残す
- セキュリティヘッダー・HTTPS リダイレクトは `public/.htaccess` と `app/Config/Filters.php` の `secureheaders`
- `docs/admin-manual.md`（管理画面の操作手順）、`docs/db.md`（テーブル定義）を書く
- 残課題：A-01 ログイン・2FA（`AdminAuth` の仮認証と `?role=general` を削除）、`admins` テーブルは作ってあるが使っていない、`events` の掃除、メールの文面

## 環境のメモ

- フレームワーク本体は `system/` にコミット済み（ADR 0001）。本番に Composer は不要
- `.env` は `.env.example` から作る。`app.hashSecret`・DB 接続・`app.adminEmail`・`ogp.font`・`captcha.*`・`openai.apiKey`
- 管理画面の切替：絞り込みと選択はすべてクエリ（`?status=` `?filter=` `?item=` `?edit=` `?period=`）。`?role=general` で一般管理者
- Vercel に仮置きする場合の差分（vercel-php、`writable/` を `/tmp` へ、外部 DB、cron の代替）は未着手。本番の Xserver 向けコードは変えず、`vercel.json` と環境変数で吸収する方針
