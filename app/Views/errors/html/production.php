<?php
/**
 * エラーページ（500）
 * レイアウトは使わず、単体で表示できるようにしておく（レイアウト自体が壊れたときのため）
 */
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>エラー｜ぼけばこ</title>
<link rel="stylesheet" href="<?= base_url('assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/base.css') ?>">
</head>
<body class="l-body">
<main class="l-main l-main--center">
<section class="l-section c-error">
<p class="c-error__code">500</p>
<h1 class="c-heading">エラーが発生しました</h1>
<p class="u-sub">時間をおいてもう一度お試しください。続く場合はお問い合わせからご連絡ください。</p>
<a class="c-button c-button--primary" href="<?= base_url('/') ?>">トップへ戻る</a>
</section>
</main>
</body>
</html>
