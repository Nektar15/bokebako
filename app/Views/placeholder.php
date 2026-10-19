<?php
/**
 * 工程0〜2 の仮画面。工程1・2で各画面の View に置き換える。
 *
 * @var string $screenId
 * @var string $title
 */
?>
<?= $this->extend('layouts/front') ?>
<?= $this->section('meta') ?><title><?= esc($screenId) ?> <?= esc($title) ?>｜ぼけばこ（仮）</title><?= $this->endSection() ?>
<?= $this->section('content') ?>
<section class="l-section">
<p class="c-tag"><?= esc($screenId) ?></p>
<h1 class="c-heading"><?= esc($title) ?></h1>
<p class="u-sub">この画面は仮です。工程1・2で本来の画面に置き換えます。</p>
</section>
<?= $this->endSection() ?>
