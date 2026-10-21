<?php

use App\Entities\Answer;
use App\Entities\Oogiri;

/**
 * U-06 回答完了画面
 *
 * @var Oogiri $oogiri
 * @var Answer $answer          直前に投稿した回答
 * @var string $answerIntentUrl 回答を含めてシェアする Web Intent URL
 * @var string $oogiriIntentUrl 大喜利だけシェアする Web Intent URL
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>回答しました｜ぼけばこ</title>
<meta name="robots" content="noindex">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/components/stamp.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u06-answered.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-answered__hero">
<svg class="p-answered__box" width="120" height="104" viewBox="0 0 120 104" aria-hidden="true">
<g class="c-stamp-drop"><path d="M42 58 L46 8 L80 12 L76 58" fill="#F2C230" stroke="#1D1A16" stroke-width="3" stroke-linejoin="round"></path><path d="M52 24 H70 M51 34 H66" stroke="#1D1A16" stroke-width="3" stroke-linecap="round"></path></g>
<rect x="10" y="50" width="100" height="50" rx="8" fill="#D6401E" stroke="#1D1A16" stroke-width="3"></rect>
<path d="M36 64 H84" stroke="#1D1A16" stroke-width="5" stroke-linecap="round"></path>
</svg>
<h1 class="c-heading">回答しました！</h1>
<p class="u-sub">あなたのボケが、ぼけばこに入りました。</p>
</section>

<section class="l-section">
<div class="c-card">
<p class="u-sub"><strong>お題：</strong><?= esc($oogiri->odai) ?></p>
<p class="p-answered__body"><?= nl2br_esc($answer->body) ?></p>
</div>
</section>

<section class="l-section">
<a class="c-button c-button--x" href="<?= esc($answerIntentUrl, 'attr') ?>" target="_blank" rel="noopener" data-js-track="share_answer" data-js-track-oogiri="<?= esc($oogiri->slug, 'attr') ?>" data-js-track-answer="<?= $answer->id ?>"><?= $this->include('partials/icon_share') ?>この回答をXでシェアする</a>
<div class="p-answered__pair">
<a class="c-button c-button--secondary p-answered__half" href="<?= esc($oogiriIntentUrl, 'attr') ?>" target="_blank" rel="noopener" data-js-track="share_recruit" data-js-track-oogiri="<?= esc($oogiri->slug, 'attr') ?>">大喜利だけ<br>シェアする</a>
<a class="c-button c-button--secondary c-button--accent p-answered__half" href="<?= site_url('o/' . $oogiri->slug . '/answers') ?>">みんなの回答<br>を見る（<?= esc((string) $oogiri->answer_count) ?>）</a>
</div>
</section>

<section class="l-section">
<div class="p-answered__next">
<p class="p-answered__next-label">NEXT</p>
<h2 class="p-answered__next-title">次は、あなたが<br>お題を出す番。</h2>
<p class="p-answered__next-body">登録なしで、すぐに開催できます。集まったボケから、あなたがベスト3を決めよう。</p>
<?= part('partials/host_cta', ['slug' => $oogiri->slug, 'from' => 'U-06']) ?>
</div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="application/json" data-js-config="answered"><?= json_encode(['slug' => $oogiri->slug], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="module" src="<?= asset_url('js/pages/answered.js') ?>"></script>
<?= $this->endSection() ?>
