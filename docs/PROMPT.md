# ぼけばこ 実装プロンプト（A-01 ログイン以外の全体）

以下を、そのまま Claude Code（または担当する開発者）への指示として使う。リポジトリ直下で実行する前提。

---

あなたは、匿名大喜利Webサービス「ぼけばこ」を PHP 8.2 / CodeIgniter 4 で実装する開発者です。管理者ログイン（A-01）を除く全機能を、段階を踏んで実装してください。完成後は別の人間が保守します。**読めばわかる、短い、驚きのないコード**を最優先にしてください。

## 1. 必ず先に読む資料（この順で）

1. `docs/CODING_RULES.md` — 全ルール。これに反するコードは書かない
2. `docs/FEATURES.md` — 画面ごとの機能一覧、URL表、Viewへ渡す値、未確定事項
3. `docs/spec/SPEC.md` — 開発仕様書 Ver.1.0 の本文（原本は `開発仕様書_坂井.docx`）
4. `docs/design/README.md` と `docs/design/*.dc.html` — 画面デザインの原本

資料の間で食い違いがあれば **仕様書 > FEATURES.md > CODING_RULES.md > デザイン** の順で優先し、気づいた食い違いを `docs/adr/` に記録する。資料にないことは作らない。足りない情報は `docs/FEATURES.md` の「6. 未確定事項」を見て、仮置きの値を決め、ADRに「仮」と明記する。

## 2. 作業の進め方

- 下の工程を **1工程 = 1ブランチ = 1 PR** で進める。工程をまたいで同時に進めない。
- 各工程の最後に、`CODING_RULES.md` 15章の完了条件を自分で確認してから報告する。
- 報告は「作ったもの／仮置きした判断／次の工程で必要な確認」の3点だけを書く。経過は書かない。
- 迷ったら、機能を足すのではなく、迷った点を ADR に書いて最も単純な選択肢を取る。
- 依存パッケージは CodeIgniter 4 本体と、テスト・整形・静的解析の開発用ツール以外を増やさない。増やす場合は ADR を書く。
- `// TODO(inner):` の印は工程4で全部消す。工程5の時点で残っていたら未完了。

## 3. 工程

### 工程0：土台（`feature/setup`）

- CodeIgniter 4 の新規プロジェクト。`docs/CODING_RULES.md` 2章のディレクトリ構成を作る（空のフォルダには `.gitkeep`）
- `.env.example`、`.editorconfig`、`.gitignore`、php-cs-fixer・PHPStan（レベル6）・PHPUnit の設定
- `app/Config/Routes.php` に `FEATURES.md` 1章のURL表をすべて定義。Controller は空の雛形まで
- `app/Enums/`：`OogiriStatus`、`AnswerStatus`、`ErrorCode`
- `adminAuth` Filter の仮実装（`CODING_RULES.md` 7-1 の表どおり。production は常に403）と、そのテスト
- 共通レイアウト `layouts/front.php`・`layouts/admin.php`、`public/assets/css/tokens.css`・`base.css`、`js/main.js`・`js/lib/api.js`
- `README.md`：セットアップ手順（ローカル・Xserver）、コマンド一覧、cron の設定例。手順は実際に動かして確認する

### 工程1：ユーザー向け画面の外側（`feature/front-views`）

- U-01〜U-18 の View・CSS・JS を `docs/design/` のデザインどおりに書き起こす。1画面1ファイル、部品は `partials/` へ
- Service は `app/Services/Fixtures/` のダミーデータを返す仮実装。`?fixture=` の切り替えは development 限定
- 各 View の先頭に「View 契約」コメントを書く（`FEATURES.md` の「Viewへ渡す値」を正とする）
- JS を切っても開催・回答・問い合わせが POST できること
- 全URLの Feature テスト（ステータスと表示される View）

### 工程2：管理画面の外側（`feature/admin-views`）

- A-02〜A-10 の View・CSS・JS。一覧・検索・ページング・状態バッジは共通部品にする
- 権限による表示の出し分け（A-04 の投稿元の列）は仮管理者の権限で確認できること
- 工程1と同じく Fixture・View 契約・Feature テスト

### 工程3：DB とモデル（`feature/database`）

- `SPEC.md` 3.3 の8テーブルをマイグレーションで作る。`CODING_RULES.md` 6章（共通カラム、インデックス、ハッシュ保存）を守る
- Entity と Model。Model は DB 読み書きだけ
- 開発用 Seeder（Fixture と同じ内容）
- 集計テーブル（S-06 の日次集計先）もここで作る

### 工程4：中身（`feature/services`）

ユーザー向け → 管理向け → 自動処理の順に、Service を本実装して Fixture を外す。

- ユーザー向け：開催、回答、回答済み判定、開催者認証（トークン・合言葉・試行制限）、結果確定、通報、お問い合わせ、状態による募集URLの振り分け
- 共通：NGワード判定、レート制限（Throttler）、運用設定の読み込みとキャッシュ、計測イベントの記録、Xシェア文の組み立て、広告の取得・表示回数・クリック計測、ページキャッシュの生成と破棄、残り時間 API、管理者メール通知
- 管理向け：各画面の操作、操作履歴、CSV入出力、キャッシュ再生成
- 自動処理（Spark コマンド、cron 1分間隔）：S-01、S-02、S-04（通報の集計）、S-05（AI判定キュー）、S-06（日次集計）、S-07（OGP生成）
- 外部連携は `app/Libraries/` に閉じ込め、キーは `.env`：OpenAI（GPT-5 nano、JSON出力、エラー時は未判定）、CAPTCHA（未確定事項3を仮決め）、GD/ImageMagick
- Service の業務ルールには PHPUnit を書く（`CODING_RULES.md` 13章の必須一覧）

### 工程5：仕上げ（`feature/release-prep`）

- 100万PV対応（`SPEC.md` 4.1）の確認：ページキャッシュの対象、OPcache、バージョン付き静的URL、PV集計の非同期化、インデックス
- k6 で回答画面・回答投稿・結果ページに毎秒20リクエストをかけ、結果を `docs/load-test.md` に記録
- セキュリティヘッダー、HTTPS リダイレクト、`.env` と `writable/` が公開ディレクトリの外にあること
- `README.md` の運用手順、管理画面マニュアル（`docs/admin-manual.md`）、DB定義書（`docs/db.md`）を書く
- 残課題を `docs/HANDOFF.md` に一覧化する（A-01 ログインと二段階認証、未確定事項の仮決め一覧、本番公開前に消す仮認証コード）

## 4. やらないこと

- A-01 管理者ログイン、二段階認証、管理者アカウント管理（最終工程で別途実装する）
- `docs/FEATURES.md` と `SPEC.md` 2.3・13.3 にある「実装しない機能」すべて
- Tailwind などのビルド工程、SPA フレームワーク、jQuery
- 仕様にない画面・項目・設定値の追加

## 5. 保守しやすさの基準（迷ったらこちらを選ぶ）

- 1ファイルの役割が1行で説明できる
- Controller を読めば、その画面で何が起きるか追える（Service の呼び出し名で処理がわかる）
- 数値と文言はコードに埋めない（運用設定、`app/Config/`、言語ファイルのどれかに置く）
- 抽象化は2回目に同じコードが出てからにする。先回りの汎用化はしない
- コメントは「なぜ」だけを日本語で書き、仕様書の ID（U-04、S-03 など）を添える
- 人が読む順にファイルを並べる：ルート → Filter → Controller → Service → Model
