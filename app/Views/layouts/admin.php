<?php
/**
 * 管理画面の共通レイアウト（PC 幅のみ）
 *
 * セクション：meta（任意）/ content（必須）/ styles / scripts
 *
 * @var string $screenId   画面ID（例：A-02）。ヘッダーとメニューの選択状態に使う
 * @var string $title      画面名
 * @var array{todo:int, inquiries:int} $badges 左メニューに出す未処理件数（AdminController::render() が渡す。なければ 0）
 * @var string|null           $notice 操作後の通知（同上）
 * @var array<string, string> $errors 入力エラー（同上）
 * @var bool                  $guest  ログイン前の画面（A-01 ログイン）。左メニューと見出しを出さない
 */
$guest ??= false;
$badges ??= ['todo' => 0, 'inquiries' => 0];
$notice ??= null;
$errors ??= [];
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= esc($title) ?>｜ぼけばこ管理</title>
<link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/admin.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/admin-button.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/admin-panel.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/admin-status.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/admin-table.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/admin-tabs.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/admin-pager.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/admin-form.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/admin-history.css') ?>">
<?= $this->renderSection('styles') ?>
</head>
<?php if ($guest): ?>
<body class="l-admin l-admin--guest">
<main class="l-admin__guest">
<?= part('partials/admin_notice', ['notice' => $notice, 'errors' => $errors]) ?>
<?= $this->renderSection('content') ?>
</main>
<?php else: ?>
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
<?= part('partials/admin_notice', ['notice' => $notice, 'errors' => $errors]) ?>
<?= $this->renderSection('content') ?>
</div>
</main>
<?php endif; ?>
<?= $this->include('partials/toast') ?>
<script type="module" src="<?= asset_url('js/main.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
