# 0014 Vercel 仮置きの OGP 画像は Vercel の画像生成で描く

- 日付：2026-10-21
- 状態：採用（Vercel 仮置きだけ。本番の Xserver は今までどおり PHP の GD）

## 決めたこと

1. **Vercel の PHP（vercel-php）には GD がなく、OGP 画像を1枚も作れない**（Xのカードは既定の画像になっていた）。Vercel では、Node の関数 `api/og.js` が `@vercel/og`（Satori）で描く
2. **流れ**：ページの og:image を `/og-image/{kind}/{key}.png?v={文言の版}` にする（`OgpService::url()`。`ogp.renderer=vercel` のとき。api/index.php が既定でこうする）→ vercel.json のルートで `api/og.js` へ → 中身を PHP の `GET /api/v1/ogp/{kind}/{key}` から受け取って描く
   - 中身は公開中のものだけ（ページで見えるものと同じ）。非表示・削除中・見つからないときは既定の画像へ転送
   - CDN には5分置く（確定・非表示が5分以内に反映される）。A-08 で文言を変えると `?v=` が変わる
   - A-08 のプレビューも、Vercel ではこの仕組みで今の文言のまま描く（`/og-image/{kind}/preview.png`）
3. **配置は PHP 版と同じ**：`resources/og/render.cjs` に、`app/Libraries/OgpImage.php` と同じ座標・色で書いた。背景のぼかした回答カードは PHP 版で描いた画像（`resources/og/backdrop.png`）をそのまま使う
4. **フォント**：Noto Sans CJK JP Bold から日本語で使う範囲を切り出した WOFF（`resources/og/NotoSansJP-Bold.woff`、約10MB。SIL Open Font License）
5. **依存**：ルートの `package.json`（`@vercel/og` だけ）。Vercel がデプロイ時に入れる。本番（Xserver）には上げない

## 影響

- OGP のデザインを変えたら、`OgpImage.php` と `resources/og/render.cjs` の両方を直す。背景の絵を変えたら `backdrop.png` も作り直す
- 文字の大きさの決め方は近似（Satori は文字幅を測れないため、文字数から決める）なので、PHP 版と行の折り返しが少し違うことがある
