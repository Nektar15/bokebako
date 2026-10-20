# 0005 工程5（仕上げ）で決めたこと

- 日付：2026-10-20
- 状態：採用

## 決めたこと

1. **JSON API（`/api/v1/*`）は CSRF トークンの代わりに「JSON 本文または `X-Requested-With: XMLHttpRequest`」を要求する**（`app/Filters/AjaxOnly.php`）。ADR 0002 の例外（計測だけ）を API 全体に広げ、画面の `<meta name="csrf-token">` は廃止した。
2. **セキュリティヘッダーは `App\Filters\SecurityHeaders`**（CodeIgniter の SecureHeaders ＋ HSTS ＋ CSP ＋ Permissions-Policy）。CSP の許可先は `Config\App::$cspSources`。HSTS は `forceGlobalSecureRequests` が true（production）のときだけ。development では Debug Toolbar がインラインの script/style を差し込むため CSP を出さない。
3. **HTTP → HTTPS と www なしへの統一は `public/.htaccess`**（Xserver は Apache）。`localhost`・`127.0.0.1` だけは http のまま通す。CodeIgniter 側の `forceGlobalSecureRequests` も本番の `.env` で true にする（`app.forceGlobalSecureRequests`）。
4. **静的ページ（U-14〜U-17）もページキャッシュの対象**（1時間）。
5. **生ログ `events` は 90 日で削除**（`StatsService::EVENT_KEEP_DAYS`。`stats:daily` の最後に実行）。ダッシュボードは `daily_stats` だけを読むので、転換率の計算に要る分だけ残せばよい。
6. **負荷試験は k6 のスクリプトをリポジトリに置く**（`tests/load/k6.js`）。結果は `docs/load-test.md` に追記していく。
7. **DB 定義書 `docs/db.md` はマイグレーションから書き起こす**。列を増やしたら両方を直す。

## 理由

- 1：結果ページ（U-10〜U-12）はページキャッシュで配信するため、HTML に埋めた CSRF トークンは別の訪問者のものになり、通報 API が常に 403 になっていた。他サイトのフォームからは JSON も独自ヘッダーも送れず、`fetch` は CORS の事前確認で止まるので、CSRF 対策として同等。API 側の変更は「通報」と「計測」の記録だけで、どちらもレート制限・重複排除がある。
- 2：SPEC 3.4「セキュリティヘッダー設定」。CODING_RULES 8-2 でインラインの style/script を書いていないので、`'unsafe-inline'` なしの CSP が通る。
- 5：SPEC 4.1「ログ集約→cronで集計」の後段。無限に溜めると A-02 の期間指定が遅くなる。

## 影響

- `public/assets/js/lib/api.js` は `X-Requested-With` を付ける。API を呼ぶ JS はすべてこの関数を通す（CODING_RULES 9-2）
- 外部スクリプトを増やすときは `Config\App::$cspSources` にも足す
- Cloudflare を前段に置くときは、Cloudflare 側の「常に HTTPS」と `.htaccess` のリダイレクトが二重になるが害はない
