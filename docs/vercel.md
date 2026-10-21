# Vercel に仮置きする（触り心地の確認用）

本番は Xserver（README「Xserver へ置く」）。Vercel は画面の操作感を確かめるための仮置きで、次の制限がある（ADR 0008）。

| 項目 | Vercel での扱い | 本番との違い |
|---|---|---|
| データベース | SQLite を `/tmp` に置く。インスタンスが起きたときに開発用データ（DevSeeder）を入れる | **数分〜数時間でデータが消えて最初の状態に戻る**。アクセスが別のインスタンスに振られると、別のデータに見えることがある |
| 書き込み（キャッシュ・セッション・ログ） | `/tmp/bokebako` | 上と同じく消える。消えるとログインし直しになる |
| cron | 1分に1回まで、リクエストのついでに `cron:minute`（S-01・S-02・S-04・S-05）を動かす | アクセスがないと進まない。日次集計（S-06）と OGP の取りこぼし（S-07）は動かない |
| OGP 画像 | Vercel の PHP に GD がないので作れない。既定の画像になる | — |
| PHP | 8.2（`vercel-php@0.6.2`） | 本番と同じ版 |
| メール・CAPTCHA・AI 判定 | 設定しなければ動かない | — |

## 0. 公開中の URL

`https://bokebako.vercel.app`（Production は `develop` ブランチを追う。プレビューの URL は Vercel のログインが要る）

## 1. GitHub

リポジトリは `https://github.com/Nektar15/bokebako`（2026-10-20 に全ブランチを push 済み。コミットの作者は Claude と lunaticbeat15@gmail.com だけ）。Vercel は `develop` ブランチを使う。

Vercel の Import 画面は GitHub の既定ブランチを読み込む。main は古いので、先に GitHub の Settings → General → Default branch を `develop` にしておく（または Import 後に Vercel の Project Settings → Git → Production Branch を `develop` にして Redeploy）。

## 2. Vercel で読み込む（ブラウザ）

1. lunaticbeat15@gmail.com で Vercel にログインし、「Add New… → Project」で `Nektar15/bokebako` を Import する（一覧に出なければ「Adjust GitHub App Permissions」で Nektar15 のリポジトリへのアクセスを許可する）
2. 設定画面で次のとおりにする
   - Framework Preset：**Other**
   - Root Directory：そのまま（`./`）
   - Build Command・Output Directory・Install Command：空のまま（`vercel.json` に書いてある）
   - Production Branch：**develop**（Project Settings → Git であとから変えてもよい）
3. 「Environment Variables」に次を入れる（**名前の「.」は「_」にする**。Vercel は「.」を使えないので、`api/index.php` が読み替える）

| 名前 | 値 |
|---|---|
| `CI_ENVIRONMENT` | `staging` |
| `app_hashSecret` | 32文字以上のランダムな文字列 |
| `app_forceGlobalSecureRequests` | `false` |
| `cookie_secure` | `true` |
| `database_default_DBDriver` | `SQLite3` |
| `database_default_database` | `/tmp/bokebako/bokebako.sqlite` |
| `database_default_hostname` | 空 |
| `devSeeder_adminPassword` | 管理画面のパスワード（**必ず設定する**。設定しないと DevSeeder の公開されたパスワードで誰でも入れる） |

4. 「Deploy」を押す。数分で `https://<プロジェクト名>.vercel.app` が開く

## 3. 確かめること

画面の右下の「全画面」ボタン（確認用。ADR 0009）で開くモーダルから、全38画面を開ける。24時間たったあとの画面（募集終了・結果発表・結果未選出・非表示）も、開発用データとしていつでもある。開催者画面・回答一覧などは、押すだけで開催者・回答者として開く。

- トップから開催 → 別のブラウザ（シークレットウィンドウ）で回答 → 開催者ページで選んで発表
- 管理画面：`https://<URL>/admin/login`、ID `dev-admin`（上位管理者）または `dev-general`（一般管理者）、パスワードは `devSeeder_adminPassword` に入れたもの
- 開発用データの開催者ページは、合言葉「ぼけばこ」で開ける（例：`/host/Zp3cW9aQ`）

うまく開かないときは Vercel の「Logs」を見る。DB の準備に失敗した場合は `/tmp/bokebako/logs/vercel-setup.log` に出るが、外からは見えないので、Logs の PHP エラーを見る。

## 4. 片付け

触り終わったら、Vercel の Project Settings → General の一番下で削除する。データは毎回消えるので、残しておいても困ることはない。
