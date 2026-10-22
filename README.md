# ぼけばこ

登録不要・24時間限定の匿名大喜利 Web サービス。PHP 8.2 / CodeIgniter 4.6 / MariaDB 10.5。

はじめて触る人は、次の順で読む。

1. `docs/HANDOFF.md` — 今どこまでできていて、次に何をするか
2. `docs/CODING_RULES.md` — 書き方のルール（必読）
3. `docs/FEATURES.md` — 画面と機能の一覧、URL 表
4. `docs/spec/SPEC.md` — 開発仕様書の本文
5. `docs/design/` — 画面デザインの原本

前提知識：PHP と CodeIgniter 4 の基本、Git の基本操作。

## ローカルで動かす

PHP は本番と同じ 8.2 を使う（8.5 では PHPStan が止まる。HANDOFF「開発ツールの入れ方」）。

```bash
composer install                # 開発用ツール（composer.lock で版を固定）
cp .env.example .env            # 必要なら app.hashSecret などを書き換える
php spark serve                 # http://localhost:8080
```

- MariaDB を用意して `.env` の `database.default.*` を設定し、`php spark migrate` → `php spark db:seed DevSeeder` を実行する。DevSeeder は全テーブルを空にしてから開発用データを入れる（状態ごとの大喜利、回答、通報、NGワード、広告、お問い合わせ、日次集計）。
- 開発用データの開催者ページは、`/host/{slug}` に Cookie `bk_host_{slug}=dev-token-{slug}` を付けるか、合言葉「ぼけばこ」で復旧して開く。スラッグと状態は `app/Database/Seeds/DevSeeder.php` の先頭にある。
- OGP 画像（S-07）を作るには日本語フォントが要る。`writable/fonts/NotoSansJP-Bold.ttf` を置くか、`.env` の `ogp.font` にフォントのパスを書く（例：`/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc`）。なければ既定の画像で代用する。
- CAPTCHA（Cloudflare Turnstile）・AI判定（OpenAI）・管理者への通知メールは、`.env` の `captcha.*`・`openai.apiKey`・`app.adminEmail` が空なら動かない（開発ではそのままでよい）。
- 本番相当で動かすときは `.env` を `CI_ENVIRONMENT = production` にする（管理画面は 403 になる。`app.hashSecret` が空だと起動時にエラー）。
- 管理画面は `http://localhost:8080/admin`（パスは `.env` の `app.adminPath`）。開発用データ（DevSeeder）には上位管理者 `dev-admin` と一般管理者 `dev-general` が入っている。パスワードはどちらも `dev-password-bokebako`（`DevSeeder::ADMIN_PASSWORD`）。一般管理者でログインすると A-04 の投稿元の列が消える。

## コマンド

| コマンド | 内容 |
|---|---|
| `php spark serve` | 開発サーバー |
| `php spark routes` | ルート一覧 |
| `php spark migrate` / `migrate:rollback` | マイグレーション |
| `php spark db:seed DevSeeder` | 開発用データ（全テーブルを入れ直す） |
| `php spark admin:create <ID> <表示名> [--role super\|general]` | 管理者を追加（A-01。最初の上位管理者の作成用） |
| `php spark cron:minute` | 1分間隔の自動処理をまとめて実行（S-01・S-02・S-04・S-05） |
| `php spark oogiri:close` / `oogiri:expire` / `reports:process` / `ai:judge` | 上の個別実行 |
| `php spark stats:daily [YYYY-MM-DD]` | 日次集計（S-06。日付省略で前日） |
| `php spark ogp:generate` | 欠けている OGP 画像をまとめて生成（S-07 の取りこぼし対策） |
| `composer check` | 整形チェック・静的解析・テストをまとめて実行 |
| `composer fix` | 整形を自動で直す |
| `vendor/bin/phpunit` | テストだけ実行（DB は SQLite の `:memory:`） |
| `vendor/bin/phpunit -c phpunit.mariadb.xml.dist` | MariaDB でテストを流す（DB `bokebako_test` を作っておく） |

開発用ツール（php-cs-fixer・PHPStan・PHPUnit）は `composer install` で入る。フレームワーク本体は `system/` にコミットしてあるので、本番サーバーに Composer は不要。

## ディレクトリ

`docs/CODING_RULES.md` 2章のとおり。要点だけ：

```
app/Controllers/{Front,Host,Admin,Api/V1}   入口。Service を呼んで View を返すだけ
app/Services/                               業務ロジック（app/Config/Services.php で service('name') に登録）
app/Libraries/                              外部連携・画像生成（OgpImage, OpenAiClient, Captcha）
app/Models/                                 DB の読み書きだけ（1テーブル1 Model）
app/Database/{Migrations,Seeds}             テーブル定義と開発用データ
app/Views/{layouts,partials,front,admin}    画面
app/Commands/                               cron から実行する処理（S-01〜S-07）
public/assets/{css,js}                      静的資産（ビルド工程なし）
tests/{Feature,Unit,_support,load}          テスト（load/ は k6 の負荷試験）
docs/                                       仕様・ルール・デザイン・ADR・運用マニュアル（admin-manual.md）・DB 定義（db.md）
```

## Xserver へ置く

Xserver はドキュメントルートが `public_html` 固定なので、公開ディレクトリの外にアプリ本体を置く。

```
/home/<account>/bokebako/          ← app/ system/ writable/ .env docs/ など（Web から見えない）
/home/<account>/<domain>/public_html/   ← public/ の中身（index.php, .htaccess, assets/, ogp/）
```

1. リポジトリを `/home/<account>/bokebako/` に置く（`public/` 以外）
2. `public/` の中身を `public_html/` にコピーする
3. `public_html/index.php` の `Paths.php` の参照先を `/home/<account>/bokebako/app/Config/Paths.php` に直す
4. `.env` を作り、`CI_ENVIRONMENT = production`、`app.baseURL`、`app.adminPath`、`app.hashSecret`、DB 接続を設定する
5. `writable/` に書き込み権限を付ける
6. `php spark migrate` を実行する（Xserver の PHP はバージョン付きのパス。例：`/usr/bin/php8.2`）
7. 最初の管理者（上位管理者）を作る：`php spark admin:create <ID> <表示名>`（パスワードは対話で入力。2人目からは管理画面の「管理者アカウント」でも追加できる）

ステージングは `CI_ENVIRONMENT = staging` とする。管理画面に IP 制限をかけるときは、どの環境でも `ADMIN_ALLOWED_IPS` に許可する IP を入れる（空なら制限しない）。

### 本番の `.env` で必ず設定するもの

| キー | 内容 |
|---|---|
| `CI_ENVIRONMENT` | `production`（エラー画面を出さない） |
| `app.baseURL` | `https://<ドメイン>/` |
| `app.forceGlobalSecureRequests` | `true`（HSTS を出す） |
| `app.adminPath` | 推測困難な値 |
| `cookie.secure` | `true`（ログインのセッション Cookie を https のときだけ送る） |
| `ADMIN_ALLOWED_IPS` | 管理画面に入れる IP（任意。カンマ区切り。空なら制限しない） |
| `app.hashSecret` | 32文字以上のランダム文字列。**一度決めたら変えない**（変えると開催者トークン・ブラウザIDの照合がすべて外れる） |
| `app.adminEmail` | 通知メールの宛先 |
| `email.fromEmail` | 通知メールの差出人（**必須**。空だと送られない。送れないときは `writable/logs` に記録） |
| `app.assetVersion` | デプロイごとに変える（例：日付）。CSS/JS のブラウザキャッシュを確実に更新する |
| `database.default.*` | MariaDB の接続 |
| `captcha.siteKey` / `captcha.secretKey` | Cloudflare Turnstile。空なら CAPTCHA を出さない |
| `openai.apiKey` | AI 補助判定。空なら「未判定」のまま |
| `ogp.font` | 日本語フォントのパス（または `writable/fonts/NotoSansJP-Bold.ttf` を置く） |

### PHP の設定（Xserver のサーバーパネル「php.ini 設定」）

- `opcache.enable = 1`、`opcache.validate_timestamps = 1`、`opcache.revalidate_freq = 60`（SPEC 4.1。デプロイ直後に古いコードが 1 分残ってよい）
- `memory_limit = 256M`（OGP 画像の生成で GD を使う）
- `upload_max_filesize = 4M`（広告画像・OGP テンプレート）

### Cloudflare（推奨。SPEC 4.1）

DNS を Cloudflare に向け、「常に HTTPS」を ON。キャッシュルールで `/assets/*` と `/ogp/*` を「すべてキャッシュ、エッジ TTL 1年」にする。HTML はキャッシュしない（既定）。

### バックアップ

- DB：Xserver の自動バックアップ（14日）に加え、`mysqldump` を日次で `writable/backup/` の外（ホーム直下など）に取る
- ファイル：`public/ogp/`（再生成できるので必須ではない）、`public/assets/ads/`（広告画像。消えると A-07 から登録し直し）、`writable/ogp-templates/`

### リリース前チェック

1. `composer check` が通る
2. `.env` の上の表がすべて埋まっている。`.env` と `writable/` が `public_html/` の外にある
3. `php spark migrate` 済み。**本番では `db:seed DevSeeder` を実行しない**（全テーブルを消し、パスワードが公開されている開発用の管理者を入れる）。`php spark admin:create` で管理者を作り、管理画面にログインできる
4. cron が登録されている（下の表）
5. `https://<ドメイン>/` が開き、`http://` が `https://` に転送される。レスポンスヘッダーに `Content-Security-Policy` と `Strict-Transport-Security` がある
6. お題を1つ開催し、別端末で回答し、確定して結果ページが出る。大喜利と回答の URL を X に投稿し、カードに OGP 画像が出る（X の Card Validator は廃止されたため、実際に投稿して確かめる）。E2E（`tests/e2e`）が通る
7. `docs/load-test.md` の手順でステージングに負荷をかけ、結果を追記する

## cron（Xserver のサーバーパネルで設定）

1分間隔。`php` はフルパスで書く。

```
* * * * *    cd /home/<account>/bokebako && /usr/bin/php8.2 spark cron:minute  >> writable/logs/cron.log 2>&1   # S-01 S-02 S-04 S-05
0 4 * * *    cd /home/<account>/bokebako && /usr/bin/php8.2 spark stats:daily  >> writable/logs/cron.log 2>&1   # S-06 日次集計（前日分）
*/10 * * * * cd /home/<account>/bokebako && /usr/bin/php8.2 spark ogp:generate >> writable/logs/cron.log 2>&1   # S-07 OGP の取りこぼし
```

`cron:minute` は `oogiri:close` → `oogiri:expire` → `reports:process` → `ai:judge` を順に実行する。1本にまとめているのは Xserver の cron の本数制限のため。

## 進め方

`docs/PROMPT.md` の工程に沿って、1工程 = 1ブランチ = 1 PR で進める。完了の条件は `docs/CODING_RULES.md` 15章。

| 工程 | ブランチ | 状態 |
|---|---|---|
| 0 土台 | `feature/setup` | 完了 |
| 1 ユーザー向け画面の外側 | `feature/front-views` | 完了 |
| 2 管理画面の外側 | `feature/admin-views` | 完了 |
| 3 DB とモデル | `feature/database` | 完了 |
| 4 中身 | `feature/database`（3と同じブランチ） | 完了 |
| 5 仕上げ | `feature/release-prep` | 完了 |
| A-01 ログイン・管理者アカウント | `feature/admin-login` | 完了（二段階認証は最終工程） |
| レビューの残り・小さな積み残し・確認 | `fix/review-leftovers`・`feature/small-leftovers`・`fix/verification` | 完了 |
| Vercel 仮置き（触り心地の確認用） | `feature/vercel-staging` | 設定は完了。上げ方は `docs/vercel.md` |
| （最終）A-01 二段階認証（TOTP） | 別途 | |
