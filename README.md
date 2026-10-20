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

```bash
cp .env.example .env            # 必要なら app.hashSecret などを書き換える
php spark serve                 # http://localhost:8080
```

- MariaDB を用意して `.env` の `database.default.*` を設定し、`php spark migrate` → `php spark db:seed DevSeeder` を実行する。DevSeeder は全テーブルを空にしてから開発用データを入れる（状態ごとの大喜利、回答、通報、NGワード、広告、お問い合わせ、日次集計）。
- 開発用データの開催者ページは、`/host/{slug}` に Cookie `bk_host_{slug}=dev-token-{slug}` を付けるか、合言葉「ぼけばこ」で復旧して開く。スラッグと状態は `app/Database/Seeds/DevSeeder.php` の先頭にある。
- OGP 画像（S-07）を作るには日本語フォントが要る。`writable/fonts/NotoSansJP-Bold.ttf` を置くか、`.env` の `ogp.font` にフォントのパスを書く（例：`/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc`）。なければ既定の画像で代用する。
- CAPTCHA（Cloudflare Turnstile）・AI判定（OpenAI）・管理者への通知メールは、`.env` の `captcha.*`・`openai.apiKey`・`app.adminEmail` が空なら動かない（開発ではそのままでよい）。
- 管理画面は `http://localhost:8080/admin`（パスは `.env` の `app.adminPath`）。development では仮の管理者（上位管理者）でログイン済みとして開く（`docs/CODING_RULES.md` 7-1）。`?role=general` を付けると一般管理者の表示（A-04 の投稿元の列が消える）を確認できる。

## コマンド

| コマンド | 内容 |
|---|---|
| `php spark serve` | 開発サーバー |
| `php spark routes` | ルート一覧 |
| `php spark migrate` / `migrate:rollback` | マイグレーション |
| `php spark db:seed DevSeeder` | 開発用データ（全テーブルを入れ直す） |
| `php spark cron:minute` | 1分間隔の自動処理をまとめて実行（S-01・S-02・S-04・S-05） |
| `php spark oogiri:close` / `oogiri:expire` / `reports:process` / `ai:judge` | 上の個別実行 |
| `php spark stats:daily [YYYY-MM-DD]` | 日次集計（S-06。日付省略で前日） |
| `php spark ogp:generate` | 欠けている OGP 画像をまとめて生成（S-07 の取りこぼし対策） |
| `composer check` | 整形チェック・静的解析・テストをまとめて実行 |
| `composer fix` | 整形を自動で直す |
| `vendor/bin/phpunit` | テストだけ実行（DB は SQLite の `:memory:`。MariaDB で流すときは `phpunit.xml.dist` の `database.tests.*` を書き換える） |

開発用ツール（php-cs-fixer・PHPStan・PHPUnit）は `composer install` で入る。フレームワーク本体は `system/` にコミットしてあるので、本番サーバーに Composer は不要。

## ディレクトリ

`docs/CODING_RULES.md` 2章のとおり。要点だけ：

```
app/Controllers/{Front,Host,Admin,Api/V1}   入口。Service を呼んで View を返すだけ
app/Services/                               業務ロジック（Fixtures/ は外側フェーズのダミーデータ）
app/Models/                                 DB の読み書きだけ
app/Views/{layouts,partials,front,admin}    画面
app/Commands/                               cron から実行する処理（S-01〜S-07）
public/assets/{css,js}                      静的資産（ビルド工程なし）
docs/                                       仕様・ルール・デザイン・ADR
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

ステージングは `CI_ENVIRONMENT = staging` とし、`ADMIN_ALLOWED_IPS` に作業者の IP を入れる。

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
| 5 仕上げ | `feature/release-prep` | |
| （最終）A-01 ログイン | 別途 | |
