<?php
/**
 * エラーページ（400：送られた内容が正しくない）
 * レイアウトは使わず、単体で表示できるようにしておく（レイアウト自体が壊れたときのため）
 */
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>受け付けられませんでした｜ぼけばこ</title>
<link rel="stylesheet" href="<?= base_url('assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/base.css') ?>">
</head>
<body class="l-body">
<main class="l-main l-main--center">
<section class="l-section c-error">
<p class="c-error__code">400</p>
<h1 class="c-heading">送られた内容を受け付けられませんでした</h1>
<p class="u-sub">前の画面に戻って、入力を確かめてからもう一度お試しください。</p>
<a class="c-button c-button--primary" href="<?= base_url('/') ?>">トップへ戻る</a>
</section>
</main>
</body>
</html>
