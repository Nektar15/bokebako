# TEST_STATUS

結果：PASS／FAIL／BLOCKED／NOT RUN／N/A。実行した日時・環境・証跡を書く。

## 自動テスト

| 日時 | 環境 | 内容 | 結果 |
|---|---|---|---|
| 2026-10-22 | ローカル（PHP 8.3.6、SQLite） | `composer check`（php-cs-fixer・PHPStan レベル 6・PHPUnit 258 件） | PASS（257 合格・1 スキップ） |

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
| X 上のカードの実表示 | — | NOT RUN（データが消えない環境が要る） |
| iPhone Safari・Android Chrome の実機 | — | NOT RUN |
| GA4 DebugView | — | BLOCKED（測定 ID なし） |
