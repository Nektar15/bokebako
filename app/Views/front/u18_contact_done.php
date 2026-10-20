<?php /* U-18 お問い合わせ送信完了 */ ?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>送信しました｜ぼけばこ</title>
<meta name="robots" content="noindex">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/components/stamp.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u18-contact.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-contact__done">
<svg width="120" height="100" viewBox="0 0 120 100" aria-hidden="true">
<g class="c-stamp-drop"><rect x="34" y="10" width="52" height="36" rx="3" fill="#FFFDF8" stroke="#1D1A16" stroke-width="3" transform="rotate(-6 60 28)"></rect><path d="M36 14 L60 32 L84 12" fill="none" stroke="#1D1A16" stroke-width="3" stroke-linejoin="round" transform="rotate(-6 60 28)"></path></g>
<rect x="10" y="46" width="100" height="50" rx="8" fill="#D6401E" stroke="#1D1A16" stroke-width="3"></rect>
<path d="M36 60 H84" stroke="#1D1A16" stroke-width="5" stroke-linecap="round"></path>
</svg>
<h1 class="c-heading c-heading--sm">送信しました</h1>
<p class="p-contact__done-lead">内容を確認のうえ、必要に応じてメールでご連絡します。<br>返信まで数日かかることがあります。</p>
<a class="c-button c-button--secondary p-contact__done-btn" href="<?= site_url('/') ?>">トップへ戻る</a>
<a class="c-button c-button--ghost" href="<?= site_url('contact') ?>">別の内容でもう一度送る</a>
</section>
<?= $this->endSection() ?>
