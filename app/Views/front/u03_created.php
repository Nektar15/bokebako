<?php
/**
 * U-03 開催完了・Xシェア画面
 *
 * @var \App\Entities\Oogiri $oogiri
 * @var string $endsAt     募集終了日時の表示（例：「9月17日 22:15」）
 * @var string $shareUrl   募集URL
 * @var string $intentUrl  X の Web Intent URL
 * @var string $hostUrl    開催者ページのURL
 * @var string $ogpUrl     募集用 OGP 画像のURL（プレビューに使う）
 * @var string $hostToken  開催者トークン（JS が localStorage に保存する）
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>大喜利を開催しました｜ぼけばこ</title>
<meta name="robots" content="noindex">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/components/stamp.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u03-created.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-created__hero">
<span class="c-stamp c-stamp--pop">開催<br>中</span>
<div>
<h1 class="c-heading c-heading--sm">大喜利を<br>開催しました！</h1>
<p class="u-sub">ここからが本番。Xで回答を集めよう。</p>
</div>
</section>

<section class="l-section">
<?= part('partials/odai_card', ['odai' => $oogiri->odai, 'extra' => '<div class="c-card__divider"></div><div class="p-created__ends"><span>募集終了</span><strong>' . esc($endsAt) . 'まで</strong></div><progress class="c-progress" value="100" max="100" aria-label="残り時間の割合"></progress>']) ?>
</section>

<section class="l-section">
<a class="c-button c-button--x" href="<?= esc($intentUrl, 'attr') ?>" target="_blank" rel="noopener" data-js-track="share_recruit" data-js-track-oogiri="<?= esc($oogiri->slug, 'attr') ?>"><?= $this->include('partials/icon_share') ?>Xで回答を募集する</a>
<div class="p-created__url">
<label class="u-visually-hidden" for="share-url">募集URL</label>
<input class="c-input u-mono p-created__url-input" id="share-url" type="text" readonly value="<?= esc($shareUrl, 'attr') ?>">
<button type="button" class="c-button c-button--secondary p-created__copy" data-js-copy="<?= esc($shareUrl, 'attr') ?>">コピー</button>
</div>
<a class="c-button c-button--secondary" href="<?= esc($hostUrl, 'attr') ?>">開催者ページを見る</a>
<p class="c-field__help p-created__hint"><?= $this->include('partials/icon_lock') ?><span>開催者ページは、このブラウザなら自動で開けます。別の端末からは、募集URLと合言葉で開けます。</span></p>
</section>

<section class="l-section">
<h2 class="p-created__h2">Xではこう表示されます</h2>
<div class="p-created__post">
<span class="p-created__avatar" aria-hidden="true"></span>
<div class="p-created__post-body">
<p class="p-created__post-text">24時間だけ大喜利やります！<br>お題：<?= esc($oogiri->odai) ?><br>#ぼけばこ</p>
<div class="p-created__ogp">
<img class="p-created__ogp-img" src="<?= esc($ogpUrl, 'attr') ?>" alt="募集用OGP画像のプレビュー" width="1200" height="630">
<span class="p-created__ogp-domain"><?= esc(parse_url(base_url(), PHP_URL_HOST) ?: '') ?></span>
</div>
</div>
</div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="application/json" data-js-config="host"><?= json_encode(['slug' => $oogiri->slug, 'token' => $hostToken], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="module" src="<?= asset_url('js/pages/created.js') ?>"></script>
<?= $this->endSection() ?>
