<?php

use App\Libraries\Passphrase;

/**
 * U-01 トップ・大喜利開催画面（U-02 開催確認モーダルを含む）
 *
 * @var list<string>                                       $templates お題テンプレート
 * @var string                                             $fromSlug  派生元の大喜利スラッグ（なければ空）
 * @var int                                                $odaiMax   お題の最大文字数
 * @var array<string, string>                              $errors    項目名 => 文言。'_' は項目に属さないエラー
 * @var array{odai:string, passphrase:string, from:string} $old 前回の入力
 * @var array{top: array|null, bottom: array|null}         $ads
 * @var string                                             $captcha CAPTCHA のサイトキー（連続投稿の制限方法が CAPTCHA のときだけ。空なら出さない）
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/components/sheet.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u01-top.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/doc.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u17-guidelines.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($errors !== []): ?><?= part('partials/ga_event', ['name' => 'event_create_error', 'params' => ['fields' => implode(',', array_keys($errors))]]) ?><?php endif; ?>
<section class="l-section p-top__hero">
<span class="c-tag">登録不要・24時間限定の大喜利</span>
<h1 class="c-heading c-heading--xl">お題を出したら、<br><em class="p-top__accent">24時間</em>だけ<br>大喜利スタート。</h1>
<p class="p-top__lead">Xでシェアして、みんなからボケを集めよう。</p>
</section>

<section class="l-section">
<form class="c-card c-card--raised p-top__form" method="post" action="<?= site_url('oogiri') ?>" data-js-once>
<?= csrf_field() ?>
<input type="hidden" name="from" value="<?= esc($old['from'] !== '' ? $old['from'] : $fromSlug, 'attr') ?>">

<?php if (isset($errors['_'])): ?>
<p class="c-field__error" role="alert"><?= esc($errors['_']) ?></p>
<?php endif; ?>

<div class="c-field">
<div class="c-field__head">
<label class="c-field__label" for="odai">お題</label>
<span class="c-field__count" id="odai-count" aria-live="polite"></span>
</div>
<textarea class="c-textarea<?= isset($errors['odai']) ? ' is-invalid' : '' ?>" id="odai" name="odai" rows="3" maxlength="<?= $odaiMax ?>" required placeholder="例：こんなコンビニ店員は嫌だ" data-js-char-counter="odai-count" data-js-odai-input data-js-ga-start="event_create_start"<?= isset($errors['odai']) ? ' aria-describedby="odai-error"' : '' ?>><?= esc($old['odai']) ?></textarea>
<?php if (isset($errors['odai'])): ?><p class="c-field__error" id="odai-error" role="alert"><?= esc($errors['odai']) ?></p><?php endif; ?>
</div>

<div class="c-field">
<div class="c-field__head">
<span class="c-field__help">テンプレートから選ぶ</span>
<button type="button" class="c-button c-button--accent p-top__random" data-js-random-odai>
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"></rect><circle cx="8" cy="8" r="1.4" fill="currentColor"></circle><circle cx="16" cy="16" r="1.4" fill="currentColor"></circle><circle cx="12" cy="12" r="1.4" fill="currentColor"></circle></svg>
ランダム
</button>
</div>
<ul class="p-top__templates">
<?php foreach ($templates as $template): ?>
<li><button type="button" class="p-top__template" data-js-template="<?= esc($template, 'attr') ?>"><?= esc($template) ?></button></li>
<?php endforeach; ?>
</ul>
</div>

<div class="c-card__divider"></div>

<div class="c-field">
<div class="c-field__head">
<label class="c-field__label" for="passphrase">合言葉<span class="c-field__required">必須</span></label>
<button type="button" class="c-field__reveal" data-js-reveal="passphrase" aria-pressed="false">表示</button>
</div>
<input class="c-input<?= isset($errors['passphrase']) ? ' is-invalid' : '' ?>" id="passphrase" name="passphrase" type="password" autocomplete="new-password" required minlength="<?= Passphrase::MIN ?>" maxlength="<?= Passphrase::MAX ?>" placeholder="あなただけが分かる言葉（<?= Passphrase::MIN ?>〜<?= Passphrase::MAX ?>文字）" value="<?= esc($old['passphrase'], 'attr') ?>" aria-describedby="<?= isset($errors['passphrase']) ? 'passphrase-error ' : '' ?>passphrase-help">
<?php if (isset($errors['passphrase'])): ?><p class="c-field__error" id="passphrase-error" role="alert"><?= esc($errors['passphrase']) ?></p><?php endif; ?>
<p class="c-field__help p-top__hint" id="passphrase-help"><?= $this->include('partials/icon_key') ?><span>別の端末から開催者ページを開くときに使います。忘れないものにしてください。</span></p>
</div>

<?= part('partials/captcha', ['siteKey' => $captcha]) ?>
<button type="submit" class="c-button c-button--primary" data-js-open-dialog="confirm-dialog">24時間大喜利を開催する</button>

<?= $this->include('partials/u02_confirm') ?>
</form>
<?= $this->include('partials/doc_sheet') ?>
</section>

<section class="l-section" id="howto">
<h2 class="p-top__h2">あそびかた</h2>
<ol class="p-top__steps">
<li class="p-top__step"><span class="p-top__num">1</span><span class="p-top__step-title">お題を出す</span><span class="p-top__step-body">登録なしで今すぐ</span></li>
<li class="p-top__step"><span class="p-top__num">2</span><span class="p-top__step-title">Xで募集</span><span class="p-top__step-body">回答は24時間だけ</span></li>
<li class="p-top__step"><span class="p-top__num p-top__num--shu">3</span><span class="p-top__step-title">ベスト3発表</span><span class="p-top__step-body">あなたが選ぶ</span></li>
</ol>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= asset_url('js/pages/top.js') ?>"></script>
<?= $this->endSection() ?>
