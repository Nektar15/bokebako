<?php
/** U-17 投稿ガイドライン（利用規約より平易な言葉で。ページキャッシュの対象） */
$rules = [
    ['実在する一般の人への攻撃', '友だち、同級生、同僚など、実在する人をからかったり、傷つけたりするボケ。'],
    ['いじめに使うこと', '学校や職場の特定の人を笑いものにするために、お題やボケを使うこと。'],
    ['個人情報', '名前、住所、電話番号、学校名、SNSのアカウントなど、人を特定できる情報。'],
    ['差別', '人種、民族、性別、障害、病気、出身地などを理由にした差別的な表現。'],
    ['脅迫', '「家に行く」など、相手を怖がらせる表現。'],
    ['過度に性的な内容', '露骨な性的表現や、性的な嫌がらせ。'],
    ['他人の権利の侵害', '歌詞や他人の作品のまる写し、他人へのなりすまし。'],
];
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>投稿ガイドライン｜ぼけばこ</title>
<meta name="description" content="ぼけばこで投稿するときに守ってほしいこと。">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/doc.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u17-guidelines.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= part('partials/ga_event', ['name' => 'guideline_view', 'params' => ['doc' => 'guidelines', 'via' => 'page']]) ?>
<section class="l-section p-guide__hero">
<span class="c-tag">投稿ガイドライン</span>
<h1 class="c-heading">みんなで笑える<br>ボケにしよう。</h1>
<p class="p-guide__lead">ぼけばこは、誰でも匿名で参加できる場所です。だからこそ、次のことだけは守ってください。</p>
</section>

<section class="l-section">
<h2 class="p-guide__h2">やめてほしいこと</h2>
<ul class="p-guide__rules">
<?php foreach ($rules as [$title, $example]): ?>
<li class="p-guide__rule"><span class="p-guide__x" aria-hidden="true">✕</span><div><h3><?= esc($title) ?></h3><p><?= esc($example) ?></p></div></li>
<?php endforeach; ?>
</ul>
</section>

<section class="l-section">
<div class="p-guide__ask"><p class="p-guide__ask-label">迷ったら</p><p class="p-guide__ask-text">そのボケ、言われた本人の前でも笑えますか？</p></div>
</section>

<section class="l-section">
<div class="c-card"><h2 class="p-guide__h3">ルールに反する投稿は</h2><p class="p-guide__p">非表示または削除されることがあります。くり返す場合は、しばらく投稿できなくなります。</p></div>
<div class="c-card p-guide__report"><span class="p-guide__report-icon"><?= $this->include('partials/icon_flag') ?></span><div><h2 class="p-guide__h3">見つけたら通報を</h2><p class="p-guide__p">ボケの横にある旗のボタンから通報できます。通報したことが、投稿した人に伝わることはありません。</p></div></div>
</section>

<section class="l-section p-guide__last" data-js-doc-skip>
<a class="c-button c-button--primary" href="<?= site_url('/') ?>">大喜利を開催する</a>
</section>
<?= $this->endSection() ?>
