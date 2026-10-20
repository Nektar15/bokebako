<?php
/**
 * U-08 開催者の本人確認（別端末からの復旧：募集URL＋合言葉）
 *
 * @var \App\Entities\Oogiri $oogiri
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
<form class="c-card c-card--raised" method="post" action="<?= site_url('host/' . $oogiri->slug . '/recover') ?>" data-js-once>
<?= csrf_field() ?>
<?php if (isset($errors['_'])): ?><p class="c-field__error" role="alert"><?= esc($errors['_']) ?></p><?php endif; ?>
<div class="c-field">
<label class="c-field__label" for="passphrase">合言葉</label>
<input class="c-input<?= isset($errors['passphrase']) ? ' is-invalid' : '' ?>" id="passphrase" name="passphrase" type="password" autocomplete="off" required>
<?php if (isset($errors['passphrase'])): ?><p class="c-field__error" role="alert"><?= esc($errors['passphrase']) ?></p><?php endif; ?>
<p class="c-field__help">合言葉は運営でも確認できません。忘れた場合、開催者ページは開けなくなります。</p>
</div>
<button type="submit" class="c-button c-button--primary">開催者ページを開く</button>
</form>
<a class="c-button c-button--ghost" href="<?= site_url('o/' . $oogiri->slug) ?>">回答ページへ戻る</a>
</section>
<?= $this->endSection() ?>
