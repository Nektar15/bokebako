# OGP 画像用フォント

`NotoSansJP-Bold.ttf` をここに置く（Noto Sans JP、SIL Open Font License。Google Fonts から入手）。
別の場所・別のフォントを使うときは `.env` の `ogp.font` に絶対パスを書く。

フォントがないときは OGP 画像を生成せず、`public/assets/img/ogp-default.png` を使う（`OgpService::url()`）。
