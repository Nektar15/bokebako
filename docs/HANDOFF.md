# 引き継ぎメモ（新しいセッション・別の開発者向け）

最終更新：2026-10-20　ブランチ：`feature/front-views`（工程1まで完了、develop 未マージ）

## 今どこまでできているか

| 工程 | 状態 | ブランチ |
|---|---|---|
| 0 土台 | 完了・develop に取り込み済み | `feature/setup` |
| 1 ユーザー向け画面の外側（U-01〜U-18） | 実装完了。**自動テスト未実行** | `feature/front-views` |
| 2 管理画面の外側（A-02〜A-10） | 未着手。Controller は仮画面（`placeholder`）を返す | `feature/admin-views`（これから） |
| 3 DB とモデル | 未着手 | |
| 4 中身（Service 本実装） | 未着手。`// TODO(inner):` が 49 箇所 | |
| 5 仕上げ | 未着手 | |
| A-01 ログイン | 最終工程で実装。それまでは仮認証（CODING_RULES 7-1） | |

## 最初にやること（新しいセッションでそのまま貼る指示）

```
docs/PROMPT.md と docs/HANDOFF.md を読んでから進めてください。
まず feature/front-views で `cp .env.example .env && composer install && composer check` を実行し、
整形・PHPStan（レベル6）・PHPUnit を通してください。落ちたものは直してコミットします。
通ったら develop にマージし、工程2（管理画面の外側）を feature/admin-views で始めてください。
```

工程1の作業環境では packagist.org に接続できず、開発ツールを入れられなかった。次の点は実行して確認が必要。

- `tests/Feature/FrontFormsTest.php` の `assertSessionHas('errors', …)`：フラッシュデータの取り方が FeatureTestTrait で期待どおりか
- `tests/Feature/FrontRoutesTest.php` の `assertSee('大喜利を<br>開催しました')`：assertSee はタグを除いて比較するので、`<br>` を含む期待値は落ちる可能性が高い。落ちたら `'開催しました'` に直す
- PHPStan：`app/Config/Services.php` の `static::settings()` 系と、Entity のマジックプロパティ（`$oogiri->slug` など）で指摘が出たら、`@property` の追記か `phpstan.neon.dist` の `ignoreErrors` で対応する

## 手で確認済みのこと（工程1）

- 全30パターンの URL（`?fixture=` の状態込み）を起動して表示。意図したリダイレクト・404 以外はすべて 200
- 送信系15パターン（開催・回答・問い合わせ・合言葉・結果確定・削除・通報 API）の遷移先とエラー文言
- ブラウザ（Playwright）で JS の動作：テンプレート選択、確認シート、通報、タップ順での順位付け、URL 必須の切替、localStorage 保存。JS エラーなし
- スマホ幅（390px）と PC 幅（1280px）の表示

## 仮置きした判断（発注者の確認待ち）

`docs/adr/` と `docs/FEATURES.md` 6章「未確定事項」が正。工程1で追加した仮置きは次の3つ。

1. 回答シェア用 URL `/o/{slug}/a/{id}`：募集中は U-04 に「シェアされたボケ」を添えて表示。終了後は募集 URL へ（未確定事項5）
2. U-13「内容を修正して再募集する」：`/?from={slug}&fix=1` でトップへ送り、新しい大喜利として開催（未確定事項7）
3. 規約・プライバシーポリシー：冒頭数条のひな形だけ置き、残りは `[発注者様支給の文面]`。本番以外では「ひな形」の注記を表示

## 工程2を始める人へ

- 管理画面の View は `docs/design/A02〜A10-*.dc.html` を書き起こす。レイアウトは `app/Views/layouts/admin.php`、左メニューは `partials/admin_sidebar.php`、CSS は `public/assets/css/admin.css` がすでにある
- 一覧・検索・ページング・状態バッジは共通部品にする（`partials/admin_*.php`、`css/components/admin-*.css`）
- Controller（`app/Controllers/Admin/*`）は雛形のみ。`placeholder()` を本来の View に置き換え、全画面が終わったら `BaseController::placeholder()` と `Views/placeholder.php` を削除する
- Service はユーザー向けと同じく Fixture を返す仮実装（`app/Services/Fixtures/`）。管理画面用の Fixture（通報、NGワード、広告、問い合わせ、設定、集計）を足す
- A-04 の投稿元の列は `service('currentAdmin')->get()->role->canViewSource()` で出し分ける。仮管理者は上位管理者なので表示される側で確認できる

## 環境のメモ

- フレームワーク本体は `system/` にコミット済み（ADR 0001）。本番に Composer は不要
- `.env` は `.env.example` から作る。`app.hashSecret` と DB 接続を入れる
- ダミー状態の切替：`?fixture=open|open_empty|awaiting|decided|decided_one|no_result|hidden|hidden_fixable|deleted|recover|not_answered`、入力エラーは `?fixture=error_ngword|error_ratelimit|error_passphrase`、広告の見本は `?ads=1`（development 限定）
