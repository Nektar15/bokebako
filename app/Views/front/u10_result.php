<?php

use App\Entities\Answer;
use App\Entities\Oogiri;

/**
 * U-10 結果発表ページ（ページキャッシュの対象）
 *
 * @var Oogiri                                               $oogiri
 * @var string                                               $ogpUrl 結果用 OGP 画像の URL
 * @var string                                               $period                    募集期間の表示（例：「9月16日 22:15〜9月17日 22:15」）
 * @var list<Answer>                                         $ranked 1位から順のベスト回答（1〜3件）
 * @var array{items: list<Answer>, hasMore: bool, page: int} $answers 全回答（新着順）
 * @var string                                               $resultIntentUrl
 * @var callable(Answer): string                             $answerIntentUrl
 * @var array{top: array|null, bottom: array|null}           $ads
 */
$first = $ranked[0] ?? null;
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>結果発表：<?= esc($oogiri->odai) ?>｜ぼけばこ</title>
<meta name="description" content="大喜利「<?= esc($oogiri->odai, 'attr') ?>」の結果発表。<?= $first !== null ? '1位：' . esc(mb_strimwidth($first->body, 0, 60, '…'), 'attr') : '' ?>（全<?= esc((string) $oogiri->answer_count) ?>回答）">
<meta property="og:title" content="結果発表：<?= esc($oogiri->odai, 'attr') ?>">
<meta property="og:description" content="<?= $first !== null ? '1位：' . esc(mb_strimwidth($first->body, 0, 60, '…'), 'attr') : '' ?>">
<meta property="og:image" content="<?= esc($ogpUrl, 'attr') ?>">
<meta property="og:url" content="<?= esc(site_url('o/' . $oogiri->slug), 'attr') ?>">
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
<span class="c-stamp c-stamp--pop p-result__stamp">結果<br>発表</span>
<span class="c-tag c-tag--ink p-result__tag">24時間大喜利・結果</span>
<h1 class="c-heading p-result__odai"><?= esc($oogiri->odai) ?></h1>
<p class="u-sub">全<?= esc((string) $oogiri->answer_count) ?>回答 ・ <?= esc($period) ?></p>
</section>

<section class="l-section">
<?php foreach ($ranked as $i => $answer): ?>
<?php if ($i === 0): ?>
<div class="p-result__first"><span class="p-result__rank p-result__rank--first"><?= esc((string) $answer->rank) ?>位</span><p class="p-result__first-body"><?= nl2br_esc($answer->body) ?></p></div>
<?php if (count($ranked) > 1): ?><div class="p-result__others"><?php endif; ?>
<?php else: ?>
<div class="p-result__other"><span class="p-result__rank"><?= esc((string) $answer->rank) ?>位</span><p class="p-result__other-body"><?= nl2br_esc($answer->body) ?></p></div>
<?php endif; ?>
<?php endforeach; ?>
<?php if (count($ranked) > 1): ?></div><?php endif; ?>
<p class="u-sub p-result__by">選んだ人：お題の開催者</p>
</section>

<section class="l-section">
<a class="c-button c-button--x" href="<?= esc($resultIntentUrl, 'attr') ?>" target="_blank" rel="noopener" data-js-track="share_result" data-js-track-oogiri="<?= esc($oogiri->slug, 'attr') ?>"><?= $this->include('partials/icon_share') ?>結果をXでシェアする</a>
<?= part('partials/host_cta', ['slug' => $oogiri->slug, 'from' => 'U-10']) ?>
</section>

<section class="l-section">
<div class="p-result__list-head"><h2 class="p-result__h2">すべての回答</h2><span class="u-sub"><?= esc((string) $oogiri->answer_count) ?>件</span></div>
<?= part('partials/answer_list', ['oogiri' => $oogiri, 'answers' => $answers, 'answerIntentUrl' => $answerIntentUrl, 'variant' => 'row']) ?>
</section>

<?= part('partials/modal_report', ['slug' => $oogiri->slug]) ?>
<?= $this->endSection() ?>
