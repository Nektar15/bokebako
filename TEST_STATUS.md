# TEST_STATUS

結果：PASS／FAIL／BLOCKED／NOT RUN／N/A。実行した日時・環境・証跡を書く。

## 自動テスト

| 日時 | 環境 | 内容 | 結果 |
|---|---|---|---|
| 2026-10-22（作業前） | ローカル（PHP 8.3.6、SQLite） | `composer check`（php-cs-fixer・PHPStan レベル 6・PHPUnit 258 件） | PASS（257 合格・1 スキップ） |
| 2026-10-22（最終） | 同上 | `composer check`（PHPUnit 303 件） | PASS（302 合格・1 スキップ：OGP のフォントがない環境） |
| 2026-10-22（最終） | ローカル、Chromium 141（Playwright 1.56.1）、iPhone 13・Pixel 7・PC 1280px の画面 | E2E 66 件（`tests/e2e`） | PASS（66/66） |
| 2026-10-22 | 同上（iPhone 13 の画面） | axe-core 4.10 WCAG 2 A/AA、主要 11 画面＋U-02 | PASS（違反 0） |
| 2026-10-24 | ローカル（PHP 8.3.6、SQLite） | `composer check`（PHPUnit 312 件。SEO の 9 件を追加） | PASS（311 合格・1 スキップ） |
| 2026-10-24 | ローカル、Chromium 141、3 つの画面 | E2E 78 件（管理画面 4 シナリオ × 3 を追加） | PASS（78/78） |
| 2026-10-24 | ローカル（4 vCPU、PHP 8.3 内蔵サーバー 8 ワーカー、MariaDB、production） | k6 0.54：4 シナリオ × 20 req/s × 30 秒 | PASS（エラー 0／4,804、p95 10〜27 ms） |
| 2026-10-24 | ローカル、MariaDB | `vercel:prepare` を 3 つ同時（GET_LOCK） | PASS（1 つだけが投入、11 件そろう。3 回） |

## 作業前の調査で FAIL だった項目の、修正後の結果

| 項目 | 修正後 |
|---|---|
| 4 文字未満の合言葉（N-02） | PASS（エラーが欄の下に出る） |
| 全角スペース付きの合言葉で本人確認（N-03） | PASS |
| 他人の回答のシェア文（N-07） | PASS（「このボケ好き」） |
| 存在しない回答 URL（N-08） | PASS（404） |
| トップの OGP（N-05） | PASS |
| Vercel の同時 40 件（N-01） | PASS（2026-10-24、TiDB Cloud につないだあと。既存・新規の大喜利とも 40/40 が 200） |

## Vercel を外部 DB（TiDB Cloud）につないだあとの確認（2026-10-24）

| 内容 | 結果 |
|---|---|
| 開発用データ 11 件がそろう | PASS（1 回目は同時の準備で 2 件欠けた → `vercel:prepare` にロック（PR #2）→ DB を作り直して PASS） |
| 同時 40 件（既存 `k7Qm2xPz`・新規 `BWLTZYCD`） | PASS（40/40） |
| E2E `main-flow`（iPhone の画面）を Vercel に向けて実行 | PASS |
| OGP 画像（新規の大喜利） | PASS（200・image/png・日本語のお題） |
| リンクのカード（募集・回答・結果・トップ） | PASS（ユーザーの画面で確認。Discord などのプレビューと見られる。X の投稿画面では未確認） |
| ランタイムエラー（直近 1 時間） | 0 件 |
| X の投稿画面（iPhone の X アプリ）でトップの URL を貼る | PASS（2026-10-24、新しい OGP 画像・タイトルのカードが出る。利用者の画面で確認） |
| 同上で回答のシェア URL（`/o/k7Qm2xPz/a/90412`）を貼る | PASS（お題と回答の入った画像、タイトルはお題） |
| PR #4 のあと：`/robots.txt`（text/plain）・`/sitemap.xml`（application/xml、10 件）・canonical | PASS |
| 20 分後（2026-10-24 03:04 UTC）もデータが残る | PASS（開発用データ 11 件＋作った `BWLTZYCD`、同時 40 件も 40/40。以前は約 10 分で消えていた） |

## 調査での手動・半自動の確認（2026-10-22）

| 内容 | 環境 | 結果 |
|---|---|---|
| 同じ大喜利へ同時 40 件 | Vercel | FAIL：200 が 11 件・404 が 29 件（N-01） |
| 4 文字未満の合言葉で開催 | ローカル、Chromium（iPhone 13） | FAIL：エラーが出ずにトップへ戻る（N-02） |
| 全角スペース付きの合言葉で別の端末から本人確認 | ローカル、Chromium（Pixel 7） | FAIL：「合言葉が違います」（N-03） |
| 他人の回答のシェア文 | ローカル | FAIL：「俺の回答」（N-07） |
| U-03 の「Xで回答を募集する」の表示 | ローカル、Chromium（iPhone 13） | PASS（実機は NOT RUN） |
| 回答 URL：作成直後・別のブラウザ・再読み込み | ローカル | PASS（Vercel では FAIL：N-01） |
| 存在しない回答 URL | ローカル | FAIL：404 でなく 302（N-08） |
| 主要 12 画面の横のはみ出し（375px） | ローカル、Chromium | PASS |
| 大喜利・回答のページの OGP メタタグ（JS なしの HTML） | Vercel | PASS（トップは FAIL：N-05） |
| X 上のカードの実表示 | — | BLOCKED（データが消えない環境が要る） |
| iPhone Safari・Android Chrome の実機 | — | NOT RUN |
| GA4 DebugView | — | BLOCKED（測定 ID なし） |
