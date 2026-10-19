<?php
/**
 * 管理画面の共通レイアウト（PC 幅のみ）
 *
 * セクション：meta（任意）/ content（必須）/ styles / scripts
 *
 * @var string $screenId   画面ID（例：A-02）。ヘッダーとメニューの選択状態に使う
 * @var string $title      画面名
 * @var array{todo:int, inquiries:int} $badges 左メニューに出す未処理件数（Controller が渡す。なければ 0）
 */
$badges ??= ['todo' => 0, 'inquiries' => 0];
$admin = service('currentAdmin');
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<meta name="robots" content="noindex, nofollow">
<title><?= esc($title) ?>｜ぼけばこ管理</title>
<link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/admin.css') ?>">
<?= $this->renderSection('styles') ?>
</head>
<body class="l-admin">
<?= $this->include('partials/admin_sidebar') ?>
<main class="l-admin__main">
<header class="l-admin__header">
<div>
<?= $this->renderSection('crumb') ?>
<div class="l-admin__title"><span class="u-mono"><?= esc($screenId) ?></span><h1><?= esc($title) ?></h1></div>
</div>
<div class="l-admin__actions"><?= $this->renderSection('actions') ?></div>
</header>
<div class="l-admin__body">
<?= $this->renderSection('content') ?>
</div>
</main>
<?= $this->include('partials/toast') ?>
<script type="module" src="<?= asset_url('js/main.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
