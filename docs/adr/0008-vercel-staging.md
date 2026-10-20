# 0008 Vercel への仮置き（触り心地の確認用）

- 日付：2026-10-20
- 状態：採用（仮置き専用。本番は Xserver のまま）

## 決めたこと

1. **本番（Xserver）向けのコードは変えず、Vercel 専用の入口 `api/index.php` を足す**。準備（書き込み先・IP・DB・cron の代わり）だけをして、本来の入口 `public/index.php` に渡す。`api/` はリポジトリ直下（CODING_RULES 2章の `app/` の外）。Vercel の PHP ランタイムが `api/` を関数として扱うため。
2. **本番側の変更は2つだけ**。`Config\Paths` が環境変数 `BOKEBAKO_WRITABLE` で書き込み先を差し替えられるようにした（設定しなければ今までどおり `writable/`）。`DevSeeder` の管理者パスワードを環境変数 `devSeeder.adminPassword` で変えられるようにした（公開される場所で、公開済みの開発用パスワードを使わないため）。
3. **DB は SQLite を `/tmp` に置く**（発注者側の判断：外部 DB を用意しない）。インスタンスが起きたときにマイグレーションと DevSeeder を流す。データは消える前提。
4. **PHP は `vercel-php@0.6.2`（PHP 8.2）**。本番と版をそろえた。GD が入っていないので OGP 画像は作れず、既定の画像になる。
5. **cron の代わりに、1分に1回まで、リクエストのついでに `cron:minute` を動かす**。Vercel Cron は無料プランだと1日1回のため。
6. **利用者の IP は `x-real-ip` から取る**。Vercel の PHP からは接続元がすべて 127.0.0.1 に見え、IP 単位のレート制限（ADR 0006-3）が全員共通になってしまうため。
7. **環境変数の名前は「.」を「_」にして登録し、入口で読み替える**（Vercel は名前に「.」を使えない）。`composer.json` は `.vercelignore` で上げない（ランタイムが `composer install` を走らせ、`ext-gd` がないので失敗する。フレームワークは `system/` にある）。
8. **GitHub は `Nektar15/bokebako`、Vercel は lunaticbeat15@gmail.com のアカウント（チーム「Goma's projects」、Hobby）**（Masato さんの指示）。コミットの作者は Claude か lunaticbeat15@gmail.com に限る。Vercel の Import は Masato さんが行う（手順は `docs/vercel.md`）。

9. **`/` と `/index.php` は、静的ファイルより先に PHP へ回す**（`vercel.json` の `routes`）。出力先を `public/` にしているので、そのままだと `public/index.php` が静的ファイルとして中身ごと返っていた（公開後に見つけて直した）。`public/.htaccess` も返さない。

## 確認したこと（ローカルで Vercel と同じ条件を再現）

プロジェクトを読み取り専用の場所に置き、別のユーザーで `php -c api/php.ini -S … -t public api/index.php`（vercel-php と同じ起動のしかた）、`CI_ENVIRONMENT=production`、SQLite、環境変数は「_」の名前で起動した。

- 全画面が 200。初回だけ DB の準備で約 1.5 秒
- 開催 → 回答 → 開催者画面 → 管理画面ログインまで通る。公開済みの開発用パスワードでは入れず、`devSeeder_adminPassword` のパスワードで入れる
- 書き込みはすべて `/tmp/bokebako` に入る

Vercel 上での実際の確認は、lunaticbeat15@gmail.com のアカウントで上げたあとに行う（HANDOFF）。

## 影響

- `.vercelignore`・`vercel.json`・`api/` は本番（Xserver）には置かなくてよい（置いても使われない）
- Vercel を使わなくなったら、`api/`・`vercel.json`・`.vercelignore`・`docs/vercel.md` を消せばよい。`Paths` と `DevSeeder` の変更は残しても害はない
