<?php

use App\Entities\Answer;
use App\Entities\Oogiri;

/**
 * U-07 回答後・回答一覧画面
 *
 * @var Oogiri                                               $oogiri
 * @var string                                               $remaining
 * @var int                                                  $progressPercent
 * @var array{items: list<Answer>, hasMore: bool, page: int} $answers
 * @var callable(Answer): string                             $answerIntentUrl
 * @var string                                               $sort  並び順（AnswerService::SORTS のキー）
 * @var array<string, string>                                $sorts 並び順 => 表示名
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>みんなのボケ｜<?= esc($oogiri->odai) ?>｜ぼけばこ</title>
<meta name="robots" content="noindex">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/components/sheet.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components/answers.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u04-answer.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u07-list.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-list__head">
<p class="p-list__odai"><?= esc($oogiri->odai) ?></p>
<div class="p-list__meta">
<?= part('partials/timer', ['slug' => $oogiri->slug, 'remaining' => $remaining, 'progressPercent' => $progressPercent, 'compact' => true]) ?>
<span class="p-list__count"><span data-js-timer-count><?= esc((string) $oogiri->answer_count) ?></span> 回答</span>
</div>
</div>

<section class="l-section">
<div class="p-list__title">
<h1 class="p-list__h1">みんなのボケ</h1>
<nav class="c-segment" aria-label="並び順">
<?php foreach ($sorts as $key => $label): ?>
<a class="c-segment__item<?= $key === $sort ? ' is-current' : '' ?>" href="<?= site_url('o/' . $oogiri->slug . '/answers' . ($key === 'new' ? '' : '?sort=' . $key)) ?>"<?= $key === $sort ? ' aria-current="true"' : '' ?> data-js-track="sort_change" data-js-track-oogiri="<?= esc($oogiri->slug, 'attr') ?>" data-js-track-from="<?= esc($key, 'attr') ?>"><?= esc($label) ?></a>
<?php endforeach; ?>
</nav>
</div>
<?= part('partials/answer_list', ['oogiri' => $oogiri, 'answers' => $answers, 'answerIntentUrl' => $answerIntentUrl, 'variant' => 'card', 'moreUrl' => site_url('o/' . $oogiri->slug . '/answers') . '?' . http_build_query(array_filter(['sort' => $sort === 'new' ? null : $sort, 'page' => $answers['page'] + 1]))]) ?>
<p class="u-sub u-center p-list__note">ぼけばこに「いいね」や投票はありません。<br>ベストは、お題を出した人が選びます。</p>
</section>

<section class="l-section">
<div class="c-card c-card--raised">
<p class="p-list__cta-text">見てるだけじゃ、もったいない。</p>
<?= part('partials/host_cta', ['slug' => $oogiri->slug, 'from' => 'U-07']) ?>
</div>
</section>

<?= part('partials/modal_report', ['slug' => $oogiri->slug]) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= asset_url('js/pages/answer.js') ?>"></script>
<?= $this->endSection() ?>
