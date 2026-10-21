# 引き継ぎメモ（新しいセッション・別の開発者向け）

最終更新：2026-10-20　ブランチ：`develop`（下の表の作業はすべて取り込み済み。`composer check` 通過、212 テスト）

## 今どこまでできているか

| 工程 | 状態 | ブランチ |
|---|---|---|
| 0 土台 | 完了 | `feature/setup` |
| 1 ユーザー向け画面の外側（U-01〜U-18） | 完了 | `feature/front-views` |
| 2 管理画面の外側（A-02〜A-10） | 完了 | `feature/admin-views` |
| 3 DB とモデル／4 中身 | 完了 | `feature/database` |
| 5 仕上げ | 完了 | `feature/release-prep` |
| 工程5後のコードレビュー（P1 5件・P2 2件） | 完了（ADR 0006） | `fix/review-stage5` |
| composer.lock の追加 | 完了（Mac・PHP 8.2.34 で作成） | `feature/composer-lock` |
| A-01 ログイン・ログアウト・管理者アカウント | 完了（ADR 0007）。仮認証は削除済み | `feature/admin-login` |
| レビューで残した点（取得上限・状態遷移・トランザクション・依存） | 完了（下の「2026-10-20 の作業」） | `fix/review-leftovers` |
| 小さな積み残し（メールのテンプレート、OGP の一括作り直し） | 完了 | `feature/small-leftovers` |
| 未確認だったことの確認（フォント・MariaDB・k6・JS なし） | 完了。見つけた不具合も修正 | `fix/verification` |
| Vercel 仮置き用の設定 | 完了（ADR 0008）。**Vercel への実際の公開は未実施**（下の「残課題 1」） | `feature/vercel-staging` |
| A-01 二段階認証（TOTP） | **未実装・最終工程**（発注者の指示。ログインの手間になるため最後に回す） | |

## 最初にやること（新しいセッションでそのまま貼る指示）

```
docs/PROMPT.md と docs/HANDOFF.md を読んでから進めてください。
develop で `cp .env.example .env && composer install && composer check` を通してください（PHP は 8.2）。
次は HANDOFF「残課題」の上から順に、指示のあるものを進めてください。
```

## 残課題

### 1. Vercel で触り心地を確かめる

- 公開済み：`https://bokebako.vercel.app`（Vercel：lunaticbeat15@gmail.com のチーム「Goma's projects」、プロジェクト `bokebako`。Production は `develop` を追う＝develop に push すると本番に入る）
- 画面の右下に「全画面」ボタン（押すと全画面へのリンクのモーダル）（確認用。Vercel だけ表示。ADR 0009）
- 管理画面はスマホ・iPad・PC に対応済み（ADR 0010）。実機の iPhone・iPad での確認はまだ（手元のブラウザで 390・820・1180・1440px を確認済み）
- 2026-10-21 時点で確認済み：`/about`、回答画面、結果画面、管理画面ログインが動き、開発用データが入っている

- 設定と手順はできている：`docs/vercel.md`（Vercel で Import → 環境変数 → Deploy）
- GitHub は `https://github.com/Nektar15/bokebako`（全ブランチ push 済み。コミットの作者は Claude と lunaticbeat15@gmail.com だけにする決まり）。Vercel は **lunaticbeat15@gmail.com のアカウント（チーム「Goma's projects」、Hobby）**で、このリポジトリの `develop` を読み込む
- 環境変数の `devSeeder_adminPassword`（管理画面のパスワード）と `app_hashSecret` は**必ず設定する**
- 上げたあとに確かめること：Vercel 上で `docs/vercel.md` の「3. 確かめること」が通るか。ローカルでは Vercel と同じ条件を再現して確認済み（ADR 0008）だが、実際の Vercel では未確認
- 制限：データは数分〜数時間で最初の状態に戻る。OGP 画像は既定の画像。cron はアクセスのついでに動く（ADR 0008）

### 2. Masato さん・発注者に決めてもらうこと

| 項目 | 今の状態 | どこに効くか |
|---|---|---|
| 二段階認証（TOTP）を全員必須にするか、上位管理者だけか。登録の流れ、端末をなくしたときの復旧 | 未着手（最終工程） | ADR 0007-6 に足す場所を書いた |
| 二段階認証が入るまで、本番で管理画面を開けてよいか | 今はパスワードだけで入れる。`ADMIN_ALLOWED_IPS` で IP を絞れる | 本番公開の判断 |
| レビュー（PR 承認）を誰がするか | CODING_RULES 12章は「もう一人のレビュー承認」が必須。GitHub を使わない指示だったので、マージはすべて手元の git で行った | GitHub（Nektar15/bokebako） に上げたあとは PR で回せる |

### 3. 発注者に確認すること（仮決め）

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
| IP 単位の投稿制限 | ブラウザ単位の5倍（既定 15回／60秒）。学校・携帯回線など IP を共有する人が多いなら上げる | 0006-3 |
| 合言葉の試行制限 | IP＋大喜利 5回、IP 20回、大喜利 30回／10分 | 0006-3 |
| 締切後に再公開して選出期限も過ぎていたとき | 次の S-02（1分以内）で結果未選出 | 0006-5 |
| 管理者ログインの数値 | 5回失敗で15分ロック、IP 20回／10分、無操作120分、最長12時間、パスワード12文字以上 | 0007-2 |
| 管理者アカウントの画面（未確定事項10） | `/{admin}/accounts`（A-01 の一部） | 0007-4 |
| 削除した大喜利の「再公開」 | 管理画面の「再公開」で削除前の状態に戻る（削除前の状態を覚えるようにした）。マニュアルは「戻せる」に合わせた | 下の「2026-10-20 の作業」 |
| U-15・U-16 の本文 | ひな形（発注者支給の本文に差し替える。本番以外では「ひな形」の注記が出る） | — |
| U-05・U-11〜U-13 の文言、お題テンプレート | コード内の固定（未確定事項9） | — |
| 仕様に画面定義がないもの | 回答シェア用 URL の表示（未確定事項5）、U-13 の「再募集」の流れ（7）など | FEATURES.md 6章 |

### 4. 本番の準備（README「Xserver へ置く」「リリース前チェック」）

- `.env`：`app.hashSecret`（一度決めたら変えない）、`cookie.secure = true`、`app.adminPath`、DB、`ADMIN_ALLOWED_IPS`（任意）
- 最初の管理者を `php spark admin:create <ID> <表示名>` で作る。**本番で DevSeeder を流さない**
- php.ini（OPcache）、cron 3本、OGP 用フォント（`writable/fonts/NotoSansJP-Bold.ttf`。Noto Sans JP の Bold。OTF でもよい。`.env` の `ogp.font` でパスを指定できる）
- Turnstile・OpenAI のキー、通知メールの宛先（`app.adminEmail`）
- Cloudflare（任意）。使う場合、非表示・削除で消した OGP 画像（`/ogp/*`）は CDN のキャッシュが切れるまで配信される。`/ogp/*` のキャッシュ時間を短くするか、消したときに CDN のパージを足す（ADR 0006-7）

### 5. まだ確かめていないこと

| 項目 | 状況 | 確かめ方 |
|---|---|---|
| Vercel 上での動作 | ローカルで同じ条件を再現して確認済み。実機は未確認 | 残課題 1 |
| 本番と同じ MariaDB 10.5 | 10.11 でテスト一式（212件）と同時操作を確認済み | ステージングで `vendor/bin/phpunit -c phpunit.mariadb.xml.dist` と、`docs/load-test.md` の同時操作 |
| ステージング（Xserver）での負荷試験 | 開発環境では 100 req/s で p95 33 ms 以下 | `docs/load-test.md`。**試験の間だけ運用設定の連続投稿の閾値を上げる**（1台から流すと IP 単位の制限に当たる） |
| メール・Turnstile・OpenAI の実際の送受信 | キーがないので未確認。メールの文面はテンプレートのテストあり | 本番のキーで1回ずつ |
| 実際の Google Fonts からの配信 | 作業環境から Google Fonts に繋がらないので、同じフォントを手元から当てて見た目を確認 | 本番で主要画面を開く |

### 6. 意図して残しているもの（直していない）

- **events の削除は 90 日固定**（`StatsService::EVENT_KEEP_DAYS`）。運用設定にすると「仕様にない設定値の追加」になるため
- **A-05 の待ち行列は新しい順に 1000 件まで読んで並べる**（`ModerationService::SCAN_LIMIT`）。それより古い未処理は件数だけ画面に出る。毎日処理していれば届かない量
- **長い回答は OGP 画像で「…」になる**（文字を小さくしても入りきらないとき）
- **Vercel の PHP には GD がない**ので、Vercel では OGP 画像を作らない（本番は GD あり）
- **PHPStan は 1.12 のまま**。2 系へ上げるなら依存の変更なので ADR を書いて別ブランチで

## 2026-10-20 の作業（工程5のあと）

### コードレビューの修正（ADR 0006）

| 指摘 | 内容 | 直し方 | テスト |
|---|---|---|---|
| 1 P1 | 別の大喜利・非表示の回答IDでも確定できた | 全件が「この大喜利の公開中の回答」でなければ全体を拒否 | `HostServiceTest`・`OogiriAdminServiceTest` |
| 2 P1 | 共有キャッシュの結果ページに「あなたの回答」 | U-10・U-12 では付けない（U-07 だけ） | `ResultPageCacheTest` |
| 3 P1 | Cookie を変えると試行回数制限が戻った | IP 単位の枠を追加。合言葉は IP＋大喜利・IP・大喜利の3枠 | `RateLimitServiceTest`・`HostServiceTest` |
| 4 P1 | 同時の確定で先の結果が上書きされた | 読んだ状態を条件にした UPDATE | `HostServiceTest` |
| 5 P2 | 「もっと見る」が1ページ目のキャッシュを返した | キャッシュキーに `page` を含め、`?page=` 付きは保存しない | `ResultPageCacheTest` |
| 6 P2 | 締切後の再公開で選出期限が空のまま | S-01 と同じ選出期限を入れる | `OogiriAdminServiceTest` |
| 7 P1 | 非表示・削除後も OGP 画像が残った | 消す・作り直す。生成失敗時は古い画像を消す | `OogiriAdminServiceTest` |

テストの土台で、ページキャッシュが `writable/cache/` に書かれて古い HTML が返っていたのも直した（`DatabaseTestCase`）。

### レビューで残した点（読みやすさ・扱いやすさ）

- **取得上限**：開催者画面（U-08）は100件ずつ「もっと見る」で読み足す（選んだ順位は保つ）。結果選択待ちではいいねを上にまとめる。A-05 の待ち行列はまとめて読む（N+1 をなくした）。テスト `HostAnswersPagingTest`
- **状態遷移**：非表示・再公開・削除（大喜利・回答・開催者の削除）も、読んだときの状態を条件にした更新にし、重なったら読み直す（`changeStatus()`）。削除前の状態を覚え、再公開で確定済みに戻る。重なり続けたら `ErrorCode::Conflict`（409）。テスト `StatusChangeTest`
- **保存と副作用の境界**：回答と回答数、通報と通報数、開催と派生開催数を、それぞれ1つのトランザクションに（`BaseModel::transaction()`）
- **Service の依存**：保存結果を左右する依存（moderation・answerAdmin・oogiriAdmin・rateLimit・host）はコンストラクタで受け取る。ログ・キャッシュ・OGP・計測・メールなどの副作用は `service()` のまま、という線にした

### 小さな積み残し

- 通知メールの文面を `app/Language/ja/Mail.php` のテンプレートに。対象画面への直リンクと自動送信の注記付き
- A-08 に「公開中の画像をまとめて作り直す」（予約制。cron の `ogp:generate` が30件ずつ進める）

### 未確認だったことの確認（`docs/load-test.md`）

- **フォント**：Noto Sans JP で OGP 画像を実際に生成。崩れを4つ直した（札の文字が枠に触れる、判子の文字がはみ出す、長いお題が右端からはみ出す、句読点が行頭に来る）
- **Google Fonts の見た目**：同じフォントを手元から当てて、スマホ幅・PC 幅で主要画面を確認。横スクロールなし、JS エラーなし
- **MariaDB 10.11**：テスト一式 212 件が通る（`phpunit.mariadb.xml.dist`）。同時に確定10本 → 1本だけ通る。10人が同時に非表示 → 回答数は1だけ減る。12人が同時に投稿 → 回答数が一致
- **k6**：100 req/s で p95 33 ms 以下、エラー 0。1台から流すと IP 単位の投稿制限に当たる（正しい動き）
- **JS なし**：開催・回答・結果の発表・お問い合わせ・管理者ログインが通る。開催者画面の「結果を発表する」が JS なしでは押せなかったのを直した

### Vercel 仮置きの設定（ADR 0008、`docs/vercel.md`）

- `api/index.php`（Vercel 専用の入口）、`vercel.json`（`vercel-php@0.6.2` = PHP 8.2）、`api/php.ini`、`.vercelignore`
- 本番側の変更は `Config\Paths`（`BOKEBAKO_WRITABLE` で書き込み先を差し替え）と `DevSeeder`（`devSeeder.adminPassword`）だけ

## 開発環境のメモ

- **PHP は 8.2 を使う**。8.5 で `composer check` を流すと PHPStan 1.12 が非推奨の警告を大量に出してメモリ不足で止まる。Mac なら `brew install php@8.2` → `export PATH="/opt/homebrew/opt/php@8.2/bin:$PATH"`（ターミナルを開くたびに）
- `composer install`（`composer.lock` あり。`config.platform.php = 8.2.0`）。`composer check` の PHPStan は `--memory-limit=1G`
- フレームワーク本体は `system/` にコミット済み（ADR 0001）。本番に Composer は不要
- `.env` は `.env.example` から作る。開発では `app.hashSecret` が空でも動く（production では必須）
- 開発用データ：`php spark migrate` → `php spark db:seed DevSeeder`。開催者ページは Cookie `bk_host_{slug}=dev-token-{slug}` か合言葉「ぼけばこ」
- 管理画面のログイン：DevSeeder の `dev-admin`（上位）／`dev-general`（一般）、パスワード `dev-password-bokebako`。テストでは `DatabaseTestCase::asAdmin()`
- テスト DB は SQLite `:memory:`（`phpunit.xml.dist`）。MariaDB で流すときは `phpunit.mariadb.xml.dist`
- OGP 画像を手元で作るときは、Noto Sans JP Bold を置いて `.env` の `ogp.font` にパスを書く

### packagist に接続できない環境で開発ツールを入れる方法

作業したクラウド環境は packagist に繋がらなかった。普通の環境では `composer install` だけでよい。

- GitHub Releases の phar（phpstan 1.12.34、php-cs-fixer 3.95.27）。`composer.lock` と同じ版にする
- PHPUnit 10.5.66 と依存（sebastian/*、phpunit/php-*、phar-io/*、theseer/tokenizer、myclabs/deep-copy、nikic/php-parser 5.9）、codeigniter/coding-standard 1.9.2、nexusphp/cs-config 3.29 を GitHub から `git clone`。リポジトリの外に置き、`classmap`／`psr-4`／`files` を並べた composer.json で `composer dump-autoload` する
- CodeIgniter の起動は `Composer\InstalledVersions` を使うので、composer の phar から `src/Composer/InstalledVersions.php` を取り出し、空の `vendor/composer/installed.php` と一緒に置く
- リポジトリの `vendor/autoload.php` はその autoload を読むだけ。`vendor/bin/php-cs-fixer` は `-d auto_prepend_file=` で同じ autoload を読ませる

## 判断の記録（ADR）

- `0001` フレームワークの同梱／`0002` 計測の CSRF 例外／`0003` 工程2／`0004` 工程3・4／`0005` 工程5
- `0006` 工程5後のコードレビューの修正
- `0007` A-01 ログインと管理者アカウント（二段階認証の前まで）
- `0008` Vercel への仮置き
- `0009` 画面一覧ナビ（確認用のモーダル）
- `0010` 管理画面をスマホ・タブレットに対応する
- `0011` お気に入りを「いいね」に変え、結果シェアに1〜3位を載せる
- `0012` OGP 画像の見た目を変え、文言と載せる順位を A-08 で編集できるようにする
