<?php
/**
 * ユーザー向け共通レイアウト
 *
 * 各画面は $this->extend('layouts/front') で継承し、次のセクションを渡す。
 *   meta    … <title> と description・OGP（任意。省略時は既定値）
 *   content … 本文（必須）
 *   styles  … 画面固有 CSS の <link>（任意）
 *   scripts … 画面固有 JS の <script type="module">（任意）
 *
 * @var array{top?: array{id:int,image:string,alt:string}|null, bottom?: array{id:int,image:string,alt:string}|null} $ads  広告枠。なければ枠ごと非表示
 */
$ads ??= [];
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= $this->include('partials/app_icons') ?>
<?= $this->renderSection('meta') ?: "<title>ぼけばこ｜登録不要・24時間限定の匿名大喜利</title>\n" . '<meta name="description" content="お題を出したら、24時間だけ大喜利スタート。Xでシェアして、みんなからボケを集めよう。">' ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/base.css') ?>">
<?php if (screen_nav() !== null): ?><link rel="stylesheet" href="<?= asset_url('css/components/screen-nav.css') ?>"><?php endif; ?>
<?= $this->renderSection('styles') ?>
<?= $this->include('partials/ga4') ?>
</head>
<body class="l-body">
<?= $this->include('partials/screen_nav') ?>
<?= $this->include('partials/header') ?>
<main class="l-main">
<?= part('partials/ad_slot', ['ad' => $ads['top'] ?? null, 'position' => 'top', 'deferred' => $ads['deferred'] ?? false]) ?>
<?= $this->renderSection('content') ?>
<?= part('partials/ad_slot', ['ad' => $ads['bottom'] ?? null, 'position' => 'bottom', 'deferred' => $ads['deferred'] ?? false]) ?>
</main>
<?= $this->include('partials/footer') ?>
<?= $this->include('partials/toast') ?>
<script type="module" src="<?= asset_url('js/main.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
