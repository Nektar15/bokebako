# 負荷試験の記録（SPEC 4.2）

目標：回答画面・回答投稿・結果ページに毎秒20リクエストで、応答 2 秒以内（p95）、エラー率 1% 未満。

## 試験の方法

- スクリプト：`tests/load/k6.js`（k6 0.54）。3つのシナリオを同時に走らせる
  - `answer_page`：U-04 回答画面の GET ＋ 残り時間 API（`/api/v1/o/{slug}/status`）
  - `answer_post`：U-04 を開いて CSRF トークンを取り、回答を POST（反復ごとに Cookie を捨てて別ブラウザとして振る舞う。レート制限は1ブラウザ 3回／60秒のため）
  - `result_page`：U-10 結果ページの GET（ページキャッシュ配信）
- 実行例（ステージング）：`k6 run -e BASE=https://staging.example.com -e DURATION=60s tests/load/k6.js`
- 対象の DB は `php spark db:seed DevSeeder` を流した状態（募集中 `k7Qm2xPz`、確定済み `q8Lr4nTb`）。試験後は回答が大量に入るので、もう一度 Seeder を流す

## 結果 1：開発環境（2026-10-20、工程5）

本番相当の環境ではない。コードの異常（N+1、ロック、エラー）がないことの確認として実施した。

- 環境：Linux コンテナ（2 vCPU）、PHP 8.3 内蔵サーバー（`PHP_CLI_SERVER_WORKERS=8`）、MariaDB 10.11 同居、`CI_ENVIRONMENT=production`、OPcache なし
- 負荷：各シナリオ 20 req/s × 30 秒（合計 3,005 リクエスト、約 100 req/s）

| シナリオ | 平均 | 中央値 | p90 | p95 | 最大 | 判定 |
|---|---|---|---|---|---|---|
| answer_page（U-04 ＋ status API） | 8.3 ms | 8.0 ms | 12.3 ms | 13.6 ms | 45.7 ms | 合格 |
| answer_post（回答 POST） | 9.5 ms | 8.9 ms | 12.0 ms | 13.3 ms | 44.8 ms | 合格 |
| result_page（U-10 キャッシュ配信） | 4.8 ms | 3.2 ms | 11.3 ms | 13.6 ms | 21.1 ms | 合格 |

- エラー：0 / 3,005（checks 100%）。回答は 600 件すべて保存され、`answer_count` も一致
- 参考：5倍（各 100 req/s、合計約 310 req/s）では p95 が 2.6 秒に達した（エラーは 0）。2 vCPU・8 ワーカーの飽和点であり、CPU を増やせば比例して伸びる

## 結果 2：開発環境（2026-10-20、レビュー修正・A-01 のあと）

工程5のあとに、IP 単位の投稿制限（ADR 0006-3）、トランザクション、条件付き更新を入れたので流し直した。

- 環境：結果 1 と同じ（Linux コンテナ、PHP 8.3 内蔵サーバー `PHP_CLI_SERVER_WORKERS=8`、MariaDB 10.11 同居、`CI_ENVIRONMENT=production`）
- 負荷：各シナリオ 20 req/s × 30 秒（合計 3,005 リクエスト、約 100 req/s）

**そのまま流すと、回答投稿は IP 単位の制限で止まる**（574件中27件だけ保存、残りは「少し時間をおいて」の画面へ。HTTP エラーではない）。k6 を1台から流すと全リクエストが同じ IP になるため。これは正しい動き。
性能を測るため、試験の間だけ運用設定（A-10）の「判定する回数と時間」を 100回／10秒 にした（IP 単位は 500回／10秒）。

| シナリオ | 平均 | 中央値 | p90 | p95 | 最大 | 判定 |
|---|---|---|---|---|---|---|
| answer_page（U-04 ＋ status API） | 14.3 ms | 13.5 ms | 21.4 ms | 24.0 ms | 50.2 ms | 合格 |
| answer_post（回答 POST） | 19.1 ms | 17.6 ms | 27.4 ms | 33.0 ms | 78.6 ms | 合格 |
| result_page（U-10 キャッシュ配信） | 5.3 ms | 4.4 ms | 7.5 ms | 9.9 ms | 39.7 ms | 合格 |

- エラー：0 / 3,005（checks 100%）。回答は 600 件すべて保存され、`answer_count`（610）と公開中の回答数（610）が一致
- 投稿が結果 1 より約 10 ms 遅いのは、トランザクションと IP 単位の制限（キャッシュの読み書きが1回増えた）のため。目標（2 秒）には十分な余裕がある
- **ステージングで流すときも、試験の間だけ閾値を上げ、終わったら元に戻す**（既定は 3回／60秒）

### 同時操作の確認（MariaDB 10.11、内蔵サーバー 12 ワーカー）

| 確認したこと | 結果 |
|---|---|
| 同じ大喜利に、別々のセッションから結果の確定を 10 本同時に送る | 確定は1本だけ。残り9本は開催者画面にエラーで戻る。順位・計測イベント・操作履歴はそれぞれ1件 |
| 管理者10人が同じ回答を同時に非表示にする | 回答数は1だけ減る（9 → 8）。操作履歴は1件 |
| 12人が同時に回答を投稿する | 回答数（20）と公開中の回答（20件）が一致 |
| テスト一式（212件）を MariaDB で流す（`phpunit.mariadb.xml.dist`） | すべて通過 |

本番は MariaDB 10.5。手元は 10.11 で確認したので、ステージングでもテスト一式と上の同時操作を一度流すとよい。

## 本番（Xserver）での確認項目

ステージングで同じスクリプトを流し、次を記録する。

1. 上の表と同じ指標（p95 が 2 秒以内か）
2. 結果ページが `writable/cache/` から配信されているか（2回目以降の応答に DB クエリがないこと。Debug Toolbar か `SHOW PROCESSLIST` で）
3. OPcache が有効か（`php -i | grep opcache.enable`。Xserver は php.ini で `opcache.enable=1` を設定する）
4. Cloudflare を前段に置く場合は、`/assets/*`・`/ogp/*` がエッジから返っているか（`cf-cache-status: HIT`）

## 100万PV 対策の実装状況（SPEC 4.1）

| 対策 | 状態 | 場所 |
|---|---|---|
| ページキャッシュ | 結果発表（U-10）・募集終了（U-11）・結果未選出（U-12）の1ページ目、紹介・規約・プライバシー・ガイドライン（U-14〜U-17）。状態が変わる操作で捨てる | `OogiriController::cachePage()`、`PageController`、`PageCacheService::purgeOogiri()` |
| 回答画面の軽量化 | 残り時間・回答数は `/api/v1/o/{slug}/status` から JS で取得。本体は DB 1〜2 クエリ | `pages/answer.js`、`StatusController` |
| OGP 画像キャッシュ | 募集用は開催時、結果用は確定時、回答用は初回アクセス時に `public/ogp/` へファイル化 | `OgpService` |
| DB 最適化 | スラッグ・状態・日時にインデックス。回答数・PV・シェア数はカウンタ列 | `app/Database/Migrations/`、`BaseModel::increment()` |
| OPcache / 静的資産 | CSS/JS は `asset_url()` でバージョン付き。`.htaccess` で1年キャッシュ＋gzip。OPcache は php.ini（README） | `app/Helpers/asset_helper.php`、`public/.htaccess` |
| PV 計測の非同期化 | `events` に1行ずつ入れ、`stats:daily` が `daily_stats` と `oogiris.pv` に反映。90日より古い生ログは削除 | `EventService`、`StatsService::aggregateDay()`・`pruneEvents()` |
| CDN | 未設定（推奨）。Cloudflare を置くときは `/assets/*`・`/ogp/*` をキャッシュ、HTML は素通し | — |
| レート制限 | 開催・回答・通報・問い合わせ・合言葉の試行（ブラウザID＋IP 単位） | `RateLimitService` |

## 結果 3：開発環境（2026-10-24、品質保証の作業のあと。指示書 第6章）

2026-10-22〜24 の変更（合言葉、並び替え、計測、開催の一覧 API、SEO）のあとに流し直した。新しく足した画面・API のシナリオ `added`（トップ ＋ `POST /api/v1/hosted` ＋ `/sitemap.xml`）を加えた。

- 環境：Linux コンテナ（4 vCPU）、PHP 8.3 内蔵サーバー（`PHP_CLI_SERVER_WORKERS=8`）、MariaDB 同居、`CI_ENVIRONMENT=production`。k6 0.54
- 負荷：4 シナリオ × 20 req/s × 30 秒（合計 4,804 リクエスト、約 160 req/s）。結果 2 と同じく、試験の間だけ連続投稿の閾値を 100 回／10 秒にした

| シナリオ | 平均 | 中央値 | p90 | p95 | 最大 | 判定 |
|---|---|---|---|---|---|---|
| answer_page（U-04 ＋ status API） | 16.2 ms | 10.6 ms | 18.6 ms | 25.2 ms | 597.6 ms | 合格 |
| answer_post（回答 POST） | 19.9 ms | 13.5 ms | 20.7 ms | 26.9 ms | 634.9 ms | 合格 |
| result_page（U-10 キャッシュ配信） | 10.6 ms | 4.6 ms | 8.2 ms | 10.1 ms | 498.7 ms | 合格 |
| added（U-01 ＋ hosted API ＋ サイトマップ） | 12.4 ms | 7.8 ms | 13.3 ms | 22.7 ms | 544.2 ms | 合格 |

- エラー：0 / 4,804（checks 100%）。回答は 600 件すべて保存され、`answer_count`（609）と公開中の回答数（609）が一致
- 最大値の 0.5〜0.6 秒は開始直後だけ（ワーカーの起動と OPcache なしの読み込み）。p95 は結果 2 とほぼ同じ
- 本番相当（Xserver・ステージング）と Vercel ではまだ流していない（Vercel は無料枠の実行時間を使うため、流すなら発注者と相談）
