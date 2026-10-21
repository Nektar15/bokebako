<?php

use App\Entities\Answer;
use App\Entities\Oogiri;

/**
 * U-12 結果未選出ページ（72時間経過で自動確定。ページキャッシュの対象）
 *
 * @var Oogiri                                               $oogiri
 * @var string                                               $ogpUrl og:image（結果用。画像がなければ既定の画像）
 * @var string                                               $period
 * @var array{items: list<Answer>, hasMore: bool, page: int} $answers
 * @var callable(Answer): string                             $answerIntentUrl
 * @var array{top: array|null, bottom: array|null}           $ads
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>終了：<?= esc($oogiri->odai) ?>｜ぼけばこ</title>
<meta name="description" content="大喜利「<?= esc($oogiri->odai, 'attr') ?>」は終了しました（全<?= esc((string) $oogiri->answer_count) ?>回答）。">
<meta property="og:title" content="<?= esc($oogiri->odai, 'attr') ?>">
<meta property="og:image" content="<?= esc($ogpUrl, 'attr') ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="bk:pv" content="<?= esc($oogiri->slug, 'attr') ?>">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/components/sheet.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/stamp.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/answers.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u10-result.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-result__head">
<span class="c-stamp c-stamp--muted p-result__stamp">選出<br>なし</span>
<span class="c-tag c-tag--sand p-result__tag">24時間大喜利・終了</span>
<h1 class="c-heading c-heading--sm p-result__odai">この大喜利は<br>終了しました</h1>
<p class="p-closed__lead">今回は、主催者によるベスト回答の選出はありませんでした。</p>
</section>

<section class="l-section">
<?= part('partials/odai_card', ['odai' => $oogiri->odai, 'tone' => 'sand', 'raised' => false, 'extra' => '<div class="c-card__divider"></div><p class="u-sub">全' . esc((string) $oogiri->answer_count) . '回答 ・ ' . esc($period) . '</p>']) ?>
<p class="p-result__notice">ベストは決まらなかったけれど、ボケは全部ここに残っています。気に入ったものは、Xでシェアできます。</p>
<?= part('partials/host_cta', ['slug' => $oogiri->slug, 'from' => 'U-12']) ?>
</section>

<section class="l-section">
<div class="p-result__list-head"><h2 class="p-result__h2">すべての回答</h2><span class="u-sub"><?= esc((string) $oogiri->answer_count) ?>件</span></div>
<?= part('partials/answer_list', ['oogiri' => $oogiri, 'answers' => $answers, 'answerIntentUrl' => $answerIntentUrl, 'variant' => 'row']) ?>
</section>

<?= part('partials/modal_report', ['slug' => $oogiri->slug]) ?>
<?= $this->endSection() ?>
