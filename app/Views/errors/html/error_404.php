<?php
/**
 * エラーページ（404）
 * レイアウトは使わず、単体で表示できるようにしておく（レイアウト自体が壊れたときのため）
 */
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ページが見つかりません｜ぼけばこ</title>
<link rel="stylesheet" href="<?= base_url('assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/base.css') ?>">
</head>
<body class="l-body">
<main class="l-main l-main--center">
<section class="l-section c-error">
<p class="c-error__code">404</p>
<h1 class="c-heading">ページが見つかりません</h1>
<p class="u-sub">URLが間違っているか、大喜利が削除された可能性があります。</p>
<a class="c-button c-button--primary" href="<?= base_url('/') ?>">トップへ戻る</a>
</section>
</main>
</body>
</html>
