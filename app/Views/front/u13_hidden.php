<?php

use App\Entities\Oogiri;
use App\Enums\OogiriStatus;

/**
 * U-13 非表示ページ（通報・NGワード・管理者判断で公開停止）
 * 検閲のロジックや通報件数は表示しない。
 *
 * @var Oogiri $oogiri  fixable が true なら修正して再募集できる
 * @var bool   $isHost                   開催者のブラウザか
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>この大喜利は現在公開されていません｜ぼけばこ</title>
<meta name="robots" content="noindex">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/u11-closed.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-closed__hero">
<svg width="132" height="100" viewBox="0 0 132 100" aria-hidden="true">
<rect x="14" y="26" width="104" height="66" rx="8" fill="#E6DDCC" stroke="#1D1A16" stroke-width="3"></rect>
<path d="M40 44 H92" stroke="#1D1A16" stroke-width="5" stroke-linecap="round"></path>
<rect x="6" y="48" width="120" height="16" fill="#F2C230" stroke="#1D1A16" stroke-width="3" transform="rotate(-14 66 56)"></rect>
<rect x="6" y="48" width="120" height="16" fill="#F2C230" stroke="#1D1A16" stroke-width="3" transform="rotate(14 66 56)"></rect>
</svg>
<h1 class="c-heading c-heading--sm">この大喜利は現在<br>公開されていません</h1>
<p class="p-closed__lead">投稿内容または通報状況を確認した結果、<br>現在公開を停止しています。</p>
</section>

<?php if (! $isHost): ?>
<section class="l-section p-closed__cta">
<a class="c-button c-button--primary" href="<?= site_url('/') ?>">新しく大喜利を開催する</a>
</section>
<?php elseif ($oogiri->status() === OogiriStatus::Deleted): ?>
<section class="l-section">
<div class="c-card p-hidden__box">
<span class="c-tag">開催者の方へ</span>
<p class="p-hidden__box-title">この大喜利は削除されています。</p>
<p class="c-field__help">新しいお題で、もう一度大喜利を開催できます。</p>
</div>
<a class="c-button c-button--primary" href="<?= site_url('/') ?>">新しく大喜利を開催する</a>
</section>
<?php elseif ($oogiri->fixable): ?>
<section class="l-section">
<div class="c-card p-hidden__box">
<span class="c-tag">開催者の方へ</span>
<p class="p-hidden__box-title">お題の内容を見直せば、もう一度募集できます。</p>
<p class="c-field__help">修正したお題は、新しい大喜利として24時間の募集を始めます。</p>
</div>
<a class="c-button c-button--primary" href="<?= site_url('/?from=' . rawurlencode($oogiri->slug) . '&fix=1') ?>">内容を修正して再募集する</a>
<a class="c-button c-button--ghost" href="<?= site_url('guidelines') ?>">投稿ガイドラインを確認する</a>
</section>
<?php else: ?>
<section class="l-section">
<div class="c-card p-hidden__box">
<span class="c-tag">開催者の方へ</span>
<p class="p-hidden__box-title">この大喜利は、ガイドラインに反する内容のため再募集できません。</p>
<a class="c-field__help" href="<?= site_url('guidelines') ?>">投稿ガイドラインを確認する</a>
</div>
<a class="c-button c-button--secondary" href="<?= site_url('/') ?>">トップへ戻る</a>
</section>
<?php endif; ?>
<?= $this->endSection() ?>
