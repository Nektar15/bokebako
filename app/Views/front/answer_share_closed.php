<?php

use App\Entities\Answer;
use App\Entities\Oogiri;

/**
 * 回答シェア用URL（/o/{slug}/a/{id}）を、募集が終わったあとに開いたとき（監査 2026-10-21）
 *
 * X のカードに回答シェア用の画像を出すため、このページで og:image を回答の画像にする。
 * 人が開いたときは、シェアされたボケと「この大喜利を見る」（結果ページなど）を出す。
 *
 * @var Oogiri $oogiri
 * @var Answer $answer
 * @var string $description og:description（回答の先頭）
 * @var string $ogpUrl  回答シェア用の画像（なければ既定の画像）
 * @var string $nextLabel 「結果を見る」など
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title><?= esc($oogiri->odai) ?>｜ぼけばこ</title>
<meta property="og:title" content="<?= esc($oogiri->odai, 'attr') ?>">
<meta property="og:description" content="<?= esc($description, 'attr') ?>">
<meta property="og:image" content="<?= esc($ogpUrl, 'attr') ?>">
<meta property="og:url" content="<?= esc(current_url(), 'attr') ?>">
<meta name="twitter:card" content="summary_large_image">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/u04-answer.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u11-closed.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= part('partials/ga_event', ['name' => 'answer_page_view', 'params' => ['oogiri' => $oogiri->slug, 'answer_id' => $answer->id]]) ?>
<section class="l-section">
<?= part('partials/odai_card', ['odai' => $oogiri->odai, 'tone' => 'sand', 'raised' => false]) ?>
</section>

<section class="l-section">
<div class="p-answer__shared">
<span class="c-card__tab">シェアされたボケ</span>
<p class="p-answer__shared-body"><?= nl2br_esc($answer->body) ?></p>
</div>
</section>

<section class="l-section p-closed__cta">
<p class="u-sub">この大喜利の募集は終了しました。</p>
<a class="c-button c-button--primary" href="<?= site_url('o/' . $oogiri->slug) ?>"><?= esc($nextLabel) ?></a>
<a class="c-button c-button--ghost" href="<?= site_url('/') ?>" data-js-track="click_create" data-js-track-slug="<?= esc($oogiri->slug, 'attr') ?>">自分も大喜利を開催する</a>
</section>
<?= $this->endSection() ?>
