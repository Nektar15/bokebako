<?php
/**
 * エラーページ（409：ほかの操作と重なった、この状態ではできない操作）
 * レイアウトは使わず、単体で表示できるようにしておく（レイアウト自体が壊れたときのため）
 */
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>操作できませんでした｜ぼけばこ</title>
<link rel="stylesheet" href="<?= base_url('assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/base.css') ?>">
</head>
<body class="l-body">
<main class="l-main l-main--center">
<section class="l-section c-error">
<p class="c-error__code">409</p>
<h1 class="c-heading">この操作はできませんでした</h1>
<p class="u-sub">ほかの操作と重なったか、今の状態ではできない操作です。前の画面に戻って、表示を読み込み直してください。</p>
<a class="c-button c-button--primary" href="<?= base_url('/') ?>">トップへ戻る</a>
</section>
</main>
</body>
</html>
