# 0004 工程3・4（DB と Service の本実装）で仮置きした判断

- 日付：2026-10-20
- 状態：採用（「仮」の項目は発注者の確認待ち）

## 決めたこと

### DB（工程3）

1. **テーブルは SPEC 3.3 の8つに、`admin_logs`・`ogp_jobs`・`daily_stats`・`events`・`ai_judgements`・`restrictions` を足した14**（`app/Database/Migrations/`）。工程2で形を決めた Entity（`AdminLog`・`OgpJob`）と、S-03〜S-06 の記録先。
2. **生IPは保存しない**。`oogiris`・`answers`・`reports` には `hash_hmac('sha256', ip, app.hashSecret)` の `ip_hash` だけを持つ（CODING_RULES 7-2）。未確定事項1「生IPを削除するまでの期間」は、保存しないことで解消。【仮】
3. **開催者トークンはハッシュだけを保存**（`oogiris.host_token_hash`）。トークン本体はブラウザの Cookie `bk_host_{slug}`（1年）と localStorage `bk:host:{slug}` に置く（U-03・U-08）。合言葉は `password_hash`。
4. **`settings` はキー・バリュー（`name` / `value`）**。SettingService が既定値（`SettingService::DEFAULTS`）に上書きして返し、1時間キャッシュする。保存後は即座にキャッシュを捨てる。
5. **`ai_judgements` が A-05 の待ち行列そのもの**。通報・NG「要確認」・異常投稿の検知で1行作り（`trigger`・`ng_detail`・`abnormal`）、AI の結果（`verdict`・`reason`）と対応済み（`resolved`）をこの行で持つ。`ModerationItem`（ADR 0003）は `ai_judgements` に `reports` の集計と対象の投稿を重ねて作る。
6. **カウンタ（`answer_count`・`share_count`・`derived_count`・`report_count`・`pv`・広告の `impressions`・`clicks`）は `SET x = x + 1`**（`BaseModel::increment()`）。回答の非表示・削除・再公開で `answer_count` を上下させる。
7. **テストは SQLite（`:memory:`）の `tests` グループ**で、テストごとにマイグレーションと `DevSeeder` を流す（`tests/_support/DatabaseTestCase`）。MariaDB 固有の SQL は書かない（Query Builder だけ）。生 SQL が要る箇所（Seeder のカウンタ集計）は `prefixTable()` を通す。

### 入力の制限（工程4）

8. **合言葉は任意、入れるなら4文字以上**（`OogiriService::PASSPHRASE_MIN`。未確定事項2）。【仮】
9. **CAPTCHA は Cloudflare Turnstile**（`app/Libraries/Captcha.php`。未確定事項3）。`.env` の `captcha.siteKey` / `captcha.secretKey` が空なら要求しない（開発・テスト）。運用設定の「制限方法」が CAPTCHA のときだけ、連続投稿を超えた環境に出す。Turnstile のスクリプトは CODING_RULES 8-2（外部スクリプトを読まない）の例外として `partials/captcha` でだけ読む。【仮】
10. **連続投稿の判定は CodeIgniter の Throttler**（トークンバケット。キーはブラウザIDハッシュ＋IPハッシュの先頭16文字）。制限方法「投稿停止」のときは `restrictions` に `ban_minutes` 分の行を入れ、以後すべての投稿を拒む。合言葉の試行は 5回／10分 で固定。
11. **NGワードの判定は「投稿拒否 > 要確認」**。要確認は投稿を通したうえで `ai_judgements` に積み、管理者へメール。有効なワードは1時間キャッシュし、A-06 の保存で捨てる。同じワード・一致方式の重複登録は入力エラー。
12. **通報の集計（S-04）は「同一ブラウザからの通報は1件」**（`reports.browser_id_hash` の DISTINCT）。`report_window_min` 分の窓で `report_count` 件に達したら自動で非表示にし（`status_before_hide` に元の状態を残す）、管理者へメール。初期値は 3件／60分（未確定事項4）。【仮】

### 自動処理と外部連携

13. **cron は `php spark cron:minute` を1分間隔で1本**（`oogiri:close` → `oogiri:expire` → `reports:process` → `ai:judge` の順に実行）。個別のコマンドも残してあるので、分けて動かしてもよい。日次集計 `stats:daily [YYYY-MM-DD]` は 4:00、`ogp:generate`（取りこぼした OGP 画像の生成）は任意の間隔。
14. **S-02 の「結果未選出」の確定日時は `decided_at`** に入れる（結果確定と同じ列。状態で区別する）。
15. **AI 補助判定は OpenAI の Chat Completions（`openai.model`、既定 `gpt-5-nano`）に JSON で返させる**（`app/Libraries/OpenAiClient.php`）。API キーが空・エラー・月間上限（`ai_monthly_limit`）超過は「未判定」のまま残す。「非表示」の判定は運用設定 `ai_auto_hide` が ON のときだけ自動で非表示にし、OFF なら A-05 に表示するだけ。【仮】
16. **OGP 画像は PHP GD で生成し、`public/ogp/{recruit|result|answer}/{key}.png` に置く**（募集・結果はスラッグ、回答は回答ID）。フォントは `.env` の `ogp.font`（空なら `writable/fonts/NotoSansJP-Bold.ttf`）。フォントがない環境では生成せず、`assets/img/ogp-default.png` を使う。生成の記録は `ogp_jobs`。
17. **管理者への通知は `app.adminEmail` へのメール**（`MailService`。空なら送らない、testing では送らない）。
18. **計測（S-06）は `events` に1行ずつ入れ、`stats:daily` が `daily_stats` にまとめる**。ページキャッシュで配信されるページ（U-10〜U-12）は PHP が動かないので、`<meta name="bk:pv">` を見て JS が `pv` を送る。それ以外のページは `visit` をサーバーで記録する。PV = `pv` + `visit`。
19. **ページキャッシュ（U-10・U-11・U-12 の1ページ目）は CodeIgniter の `cachePage()`**。状態が変わる操作（確定・非表示・削除・回答の増減）で `PageCacheService::purgeOogiri()` が3ページ分を捨てる。
20. **開催者ページの復旧用に `POST /host/{slug}/restore`** を追加（localStorage に残っていたトークンを Cookie に戻す。U-08 の JS からだけ使う）。Cookie が消えた端末でも合言葉なしで戻れるようにするため。
21. **トークンを積んだ Cookie はリダイレクトに写す**（`redirect()->withCookies()`）。CodeIgniter の `redirect()` は共有 Response の Cookie を持たないため、これがないと開催直後に開催者ページへ入れない。

## 理由

- 2・3：CODING_RULES 7-2「ハッシュで持つ」。生IPを持たなければ削除の期間を決める必要がない。
- 5：待ち行列・検知内容・AI 判定・対応済みは同じ対象について1行で済み、A-05 の表示と件数がそのまま取れる。
- 7：`composer check` を DB サーバーなしで通すため。
- 9：無料で、スクリプト1本とサーバー側の検証 URL だけで済む。差し替えやすいよう `Captcha` クラスに閉じ込めた。
- 13：Xserver の cron は本数に上限があり、1分間隔のジョブは1本にまとめたほうが管理しやすい。
- 16：SPEC 7「PHP GD で生成」。フォントはライセンスの都合でリポジトリに入れず、置き場所だけ決めた。
- 18：ページキャッシュを使う以上、サーバー側では数えられない。
- 21：手で確認した際に見つけた不具合（テストでも検証：`FrontFormsTest::testCreateOogiriRedirectsToCreatedAndStores`）。

## 影響

- `docs/FEATURES.md` 6章の未確定事項 1〜4 は「仮決め」として本 ADR を参照
- `.env.example` に `app.adminEmail`・`ogp.font`・`captcha.secretKey` を追加（`captcha.secret` から改名）
- 本番の準備：`writable/fonts/NotoSansJP-Bold.ttf` を置く、`app.adminEmail` と `captcha.*`・`openai.apiKey` を設定する、cron を README のとおり登録する
- A-01 実装時に `AdminAuth` の仮認証と `?role=general` を削除する（ADR 0003）
