<?php

use App\Entities\Oogiri;

/**
 * U-08 開催者の本人確認（別端末からの復旧：募集URL＋合言葉）
 *
 * @var Oogiri                $oogiri
 * @var array<string, string> $errors
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>開催者の確認｜ぼけばこ</title>
<meta name="robots" content="noindex">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/u08-host.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-recover">
<div class="p-recover__icon"><?= $this->include('partials/icon_lock') ?></div>
<h1 class="c-heading c-heading--sm">開催者の確認</h1>
<p class="u-sub">このブラウザには開催者の情報がありません。開催したときに決めた合言葉を入力してください。</p>
<?= part('partials/odai_card', ['odai' => $oogiri->odai, 'raised' => false]) ?>
<form class="u-hidden" method="post" action="<?= site_url('host/' . $oogiri->slug . '/restore') ?>" data-js-restore-form><?= csrf_field() ?><input type="hidden" name="token" value="" data-js-restore-token></form>
<form class="c-card c-card--raised" method="post" action="<?= site_url('host/' . $oogiri->slug . '/recover') ?>" data-js-once>
<?= csrf_field() ?>
<?php if (isset($errors['_'])): ?><p class="c-field__error" role="alert"><?= esc($errors['_']) ?></p><?php endif; ?>
<div class="c-field">
<div class="c-field__head">
<label class="c-field__label" for="passphrase">合言葉</label>
<button type="button" class="c-field__reveal" data-js-reveal="passphrase" aria-pressed="false">表示</button>
</div>
<input class="c-input<?= isset($errors['passphrase']) ? ' is-invalid' : '' ?>" id="passphrase" name="passphrase" type="password" autocomplete="current-password" required>
<?php if (isset($errors['passphrase'])): ?><p class="c-field__error" role="alert"><?= esc($errors['passphrase']) ?></p><?php endif; ?>
<p class="c-field__help">合言葉は運営でも確認できません。忘れた場合、開催者ページは開けなくなります。</p>
</div>
<button type="submit" class="c-button c-button--primary">開催者ページを開く</button>
</form>
<a class="c-button c-button--ghost" href="<?= site_url('o/' . $oogiri->slug) ?>">回答ページへ戻る</a>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="application/json" data-js-config="host"><?= json_encode(['slug' => $oogiri->slug], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="module" src="<?= asset_url('js/pages/recover.js') ?>"></script>
<?= $this->endSection() ?>
