<?php
/**
 * U-18 お問い合わせ
 *
 * @var array<string, string>                                     $types            種別の値 => 表示名
 * @var list<string>                                              $urlRequiredTypes 対象URLが必須になる種別
 * @var array<string, string>                                     $errors
 * @var array{type:string, email:string, url:string, body:string} $old
 * @var string                                                    $captcha CAPTCHA のサイトキー（空なら出さない）
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>お問い合わせ｜ぼけばこ</title>
<meta name="description" content="ぼけばこへのお問い合わせ。不具合の報告、権利侵害の申し立てなど。">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/u18-contact.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-contact__head">
<h1 class="c-heading">お問い合わせ</h1>
<p class="u-sub">投稿の削除依頼や権利侵害のご連絡、広告掲載のご相談もこちらから。</p>
</section>

<section class="l-section">
<form class="c-card c-card--raised p-contact__form" method="post" action="<?= site_url('contact') ?>" data-js-once data-js-contact-form data-js-url-required="<?= esc(implode(',', $urlRequiredTypes), 'attr') ?>">
<?= csrf_field() ?>
<fieldset class="c-field p-contact__types">
<legend class="c-field__label">お問い合わせの種類<span class="c-req">必須</span></legend>
<div class="p-contact__chips">
<?php foreach ($types as $value => $label): ?>
<label class="p-contact__chip<?= $old['type'] === $value ? ' is-on' : '' ?>"><input type="radio" name="type" value="<?= esc($value, 'attr') ?>" required<?= $old['type'] === $value ? ' checked' : '' ?>><?= esc($label) ?></label>
<?php endforeach; ?>
</div>
<?php if (isset($errors['type'])): ?><p class="c-field__error" role="alert"><?= esc($errors['type']) ?></p><?php endif; ?>
</fieldset>

<div class="c-field">
<label class="c-field__label" for="email">メールアドレス<span class="c-req">必須</span></label>
<input class="c-input<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com" value="<?= esc($old['email'], 'attr') ?>">
<?php if (isset($errors['email'])): ?><p class="c-field__error" role="alert"><?= esc($errors['email']) ?></p><?php endif; ?>
<p class="c-field__help">返信が必要なときに使います。公開されることはありません。</p>
</div>

<div class="c-field">
<label class="c-field__label" for="url">対象のURL<span class="c-req u-hidden" data-js-url-req>必須</span></label>
<input class="c-input<?= isset($errors['url']) ? ' is-invalid' : '' ?>" id="url" name="url" type="url" placeholder="https://bokebako.com/o/..." value="<?= esc($old['url'], 'attr') ?>" data-js-url-input>
<?php if (isset($errors['url'])): ?><p class="c-field__error" role="alert"><?= esc($errors['url']) ?></p><?php endif; ?>
<p class="c-field__help">投稿削除依頼・権利侵害の場合は、対象の大喜利のURLをご記入ください。</p>
</div>

<div class="c-field">
<label class="c-field__label" for="body">お問い合わせ内容<span class="c-req">必須</span></label>
<textarea class="c-textarea p-contact__body<?= isset($errors['body']) ? ' is-invalid' : '' ?>" id="body" name="body" rows="6" maxlength="2000" required placeholder="できるだけ具体的にご記入ください"><?= esc($old['body']) ?></textarea>
<?php if (isset($errors['body'])): ?><p class="c-field__error" role="alert"><?= esc($errors['body']) ?></p><?php endif; ?>
</div>

<?= part('partials/captcha', ['siteKey' => $captcha]) ?>

<button type="submit" class="c-button c-button--primary">送信する</button>
<p class="c-field__help u-center">送信前に<a href="<?= site_url('privacy') ?>">プライバシーポリシー</a>をご確認ください。</p>
</form>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= asset_url('js/pages/contact.js') ?>"></script>
<?= $this->endSection() ?>
