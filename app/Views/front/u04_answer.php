<?php

use App\Entities\Answer;
use App\Entities\Oogiri;

/**
 * U-04 大喜利回答画面（U-05 回答確認モーダルを含む）
 *
 * @var Oogiri                                     $oogiri
 * @var string                                     $remaining       残り時間の表示（例：「18時間32分」）
 * @var int                                        $progressPercent 残り時間バーの幅（0〜100）
 * @var int                                        $answerMax       回答の最大文字数
 * @var array<string, string>                      $errors          項目名 => 文言。'_' は項目に属さないエラー
 * @var array{body:string}                         $old
 * @var Answer|null                                $sharedAnswer    回答シェア用URLで開いたとき、添えて表示する回答
 * @var bool                                       $isHost          開催者本人が開いている（BUG-003）
 * @var string                                     $recruitIntent   開催者本人のときだけ、募集シェアの Web Intent URL
 * @var array{top: array|null, bottom: array|null} $ads
 * @var string                                     $ogpUrl  OGP 画像の URL（募集用。回答シェア用URLなら回答用）
 * @var string                                     $captcha CAPTCHA のサイトキー（空なら出さない）
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title><?= esc($oogiri->odai) ?>｜ぼけばこ</title>
<meta name="description" content="24時間限定の大喜利「<?= esc($oogiri->odai, 'attr') ?>」に、登録なしで匿名回答できます。">
<meta property="og:title" content="<?= esc($oogiri->odai, 'attr') ?>">
<meta property="og:description" content="<?= $sharedAnswer !== null ? esc(mb_strimwidth($sharedAnswer->body, 0, 120, '…'), 'attr') : '24時間限定 回答募集中。登録なしでボケられます。' ?>">
<meta property="og:image" content="<?= esc($ogpUrl, 'attr') ?>">
<meta property="og:url" content="<?= esc(current_url(), 'attr') ?>">
<meta name="twitter:card" content="summary_large_image">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/components/sheet.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u04-answer.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= part('partials/ga_event', ['name' => 'event_page_view', 'params' => ['oogiri' => $oogiri->slug, 'status' => 'open']]) ?>
<?php if ($sharedAnswer !== null): ?><?= part('partials/ga_event', ['name' => 'answer_page_view', 'params' => ['oogiri' => $oogiri->slug, 'answer_id' => $sharedAnswer->id]]) ?><?php endif; ?>
<?php if ($errors !== []): ?><?= part('partials/ga_event', ['name' => 'answer_error', 'params' => ['oogiri' => $oogiri->slug, 'fields' => implode(',', array_keys($errors))]]) ?><?php endif; ?>
<section class="l-section p-answer__head">
<div class="p-answer__status">
<span class="c-tag c-tag--ink p-answer__live"><span class="p-answer__dot" aria-hidden="true"></span>回答募集中</span>
<span class="p-answer__count">現在 <strong data-js-timer-count><?= esc((string) $oogiri->answer_count) ?></strong> 回答</span>
</div>
<?= part('partials/odai_card', ['odai' => $oogiri->odai, 'size' => 'lg']) ?>
<?= part('partials/timer', ['slug' => $oogiri->slug, 'remaining' => $remaining, 'progressPercent' => $progressPercent]) ?>
</section>

<?php if ($isHost): ?>
<section class="l-section">
<div class="c-card c-card--raised p-answer__host">
<p class="p-answer__host-title">あなたが開催した大喜利です</p>
<?= part('partials/share_buttons', ['intentUrl' => $recruitIntent, 'label' => 'Xで回答を募集する', 'track' => 'share_recruit', 'slug' => $oogiri->slug, 'url' => site_url('o/' . $oogiri->slug), 'title' => 'お題：' . $oogiri->odai]) ?>
<a class="c-button c-button--secondary" href="<?= site_url('host/' . $oogiri->slug) ?>">開催者ページを見る</a>
</div>
</section>
<?php endif; ?>

<?php if ($sharedAnswer !== null): ?>
<section class="l-section">
<div class="p-answer__shared">
<span class="c-card__tab">シェアされたボケ</span>
<p class="p-answer__shared-body"><?= nl2br_esc($sharedAnswer->body) ?></p>
</div>
</section>
<?php endif; ?>

<section class="l-section">
<form class="c-card p-answer__form" method="post" action="<?= site_url('o/' . $oogiri->slug . '/answers') ?>" data-js-once>
<?= csrf_field() ?>
<?php if (isset($errors['_'])): ?><p class="c-field__error" role="alert"><?= esc($errors['_']) ?></p><?php endif; ?>
<div class="c-field">
<div class="c-field__head">
<label class="c-field__label" for="body">あなたのボケ</label>
<span class="c-field__count" id="body-count" aria-live="polite"></span>
</div>
<textarea class="c-textarea<?= isset($errors['body']) ? ' is-invalid' : '' ?>" id="body" name="body" rows="4" maxlength="<?= $answerMax ?>" required placeholder="ここにボケを書く" data-js-char-counter="body-count" data-js-answer-input data-js-ga-start="answer_start" data-js-ga-oogiri="<?= esc($oogiri->slug, 'attr') ?>"<?= isset($errors['body']) ? ' aria-describedby="body-error"' : '' ?>><?= esc($old['body']) ?></textarea>
<?php if (isset($errors['body'])): ?><p class="c-field__error" id="body-error" role="alert"><?= esc($errors['body']) ?></p><?php endif; ?>
<p class="c-field__help">名前も登録もいりません。匿名で投稿されます。</p>
</div>
<?= part('partials/captcha', ['siteKey' => $captcha]) ?>
<button type="submit" class="c-button c-button--primary" data-js-open-dialog="answer-confirm" data-js-answer-submit>回答する</button>
<p class="c-field__error u-hidden" data-js-closed-note role="alert">募集は終了しました。結果発表までお待ちください。</p>

<dialog class="c-sheet" id="answer-confirm" aria-labelledby="answer-confirm-title">
<div class="c-sheet__form">
<div class="c-sheet__handle" aria-hidden="true"></div>
<h2 class="c-sheet__title" id="answer-confirm-title">このボケで回答しますか？</h2>
<div class="c-field"><span class="c-field__help">お題</span><p class="p-answer__confirm-odai"><?= esc($oogiri->odai) ?></p></div>
<div class="c-sheet__mybokeh"><span class="c-card__tab">あなたのボケ</span><p data-js-answer-preview></p></div>
<p class="c-field__help">個人攻撃・個人情報・差別的な内容は書かないでください。問題のある投稿は非表示になります。<a href="<?= site_url('guidelines') ?>">投稿ガイドライン</a></p>
<div class="c-sheet__actions">
<button type="submit" class="c-button c-button--primary">同意して回答する</button>
<button type="button" class="c-button c-button--ghost" data-js-close-dialog>修正する</button>
</div>
</div>
</dialog>
</form>
</section>

<section class="l-section">
<div class="p-answer__locked">
<?= $this->include('partials/icon_lock') ?>
<p><strong>みんなのボケは、回答すると見られます。</strong><br><span class="u-sub">まずは誰にも引っ張られず、自分の頭で。</span></p>
</div>
<div class="p-answer__report">
<button type="button" class="c-button c-button--ghost p-answer__report-btn" data-js-open-dialog="report-dialog" data-js-report-type="odai" data-js-report-id="<?= $oogiri->id ?>"><?= $this->include('partials/icon_flag') ?>このお題を通報する</button>
</div>
</section>

<section class="l-section">
<?= part('partials/host_entry', ['slug' => $oogiri->slug]) ?>
</section>

<?= part('partials/modal_report', ['slug' => $oogiri->slug]) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= asset_url('js/pages/answer.js') ?>"></script>
<?= $this->endSection() ?>
