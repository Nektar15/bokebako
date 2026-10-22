# TEST_STATUS

結果：PASS／FAIL／BLOCKED／NOT RUN／N/A。実行した日時・環境・証跡を書く。

## 自動テスト

| 日時 | 環境 | 内容 | 結果 |
|---|---|---|---|
| 2026-10-22（作業前） | ローカル（PHP 8.3.6、SQLite） | `composer check`（php-cs-fixer・PHPStan レベル 6・PHPUnit 258 件） | PASS（257 合格・1 スキップ） |
| 2026-10-22（最終） | 同上 | `composer check`（PHPUnit 303 件） | PASS（302 合格・1 スキップ：OGP のフォントがない環境） |
| 2026-10-22（最終） | ローカル、Chromium 141（Playwright 1.56.1）、iPhone 13・Pixel 7・PC 1280px の画面 | E2E 66 件（`tests/e2e`） | PASS（66/66） |
| 2026-10-22 | 同上（iPhone 13 の画面） | axe-core 4.10 WCAG 2 A/AA、主要 11 画面＋U-02 | PASS（違反 0） |

## 作業前の調査で FAIL だった項目の、修正後の結果

| 項目 | 修正後 |
|---|---|
| 4 文字未満の合言葉（N-02） | PASS（エラーが欄の下に出る） |
| 全角スペース付きの合言葉で本人確認（N-03） | PASS |
| 他人の回答のシェア文（N-07） | PASS（「このボケ好き」） |
| 存在しない回答 URL（N-08） | PASS（404） |
| トップの OGP（N-05） | PASS |
| Vercel の同時 40 件（N-01） | **BLOCKED**（外部 DB の用意待ち。SQLite のままでは変わらない） |

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
