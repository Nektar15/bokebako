<?php
/** U-14 サービス紹介・遊び方ページ（本文は発注者が確認・修正する。ページキャッシュの対象） */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>ぼけばこ とは｜登録不要・24時間限定の大喜利</title>
<meta name="description" content="ぼけばこは、登録なしで24時間限定の大喜利を開催できるサービスです。お題を出してXで募集、集まったボケからベスト3を選ぶだけ。">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/u14-about.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-about__hero">
<span class="c-tag">ぼけばことは</span>
<h1 class="c-heading c-heading--xl">登録なしで、<br><em class="p-about__accent">24時間</em>だけの<br>大喜利を。</h1>
<p class="p-about__lead">お題を出して、Xで回答を募集。集まったボケから、あなたがベスト3を選ぶ。ぼけばこは、それだけのシンプルな大喜利サービスです。</p>
<a class="c-button c-button--primary" href="<?= site_url('/') ?>">大喜利を開催する</a>
</section>

<section class="l-section">
<h2 class="p-about__h2">あそびかた</h2>
<ol class="p-about__steps">
<li class="p-about__step"><span class="p-about__num">1</span><div><h3>お題を出す</h3><p>トップページでお題を入力して開催。テンプレートやランダムなお題も使えます。会員登録はいりません。</p></div></li>
<li class="p-about__step"><span class="p-about__num">2</span><div><h3>Xで回答を集める</h3><p>発行された募集URLをXでシェア。回答は匿名で、募集は24時間で自動的に締め切られます。</p></div></li>
<li class="p-about__step"><span class="p-about__num p-about__num--shu">3</span><div><h3>ベスト3を発表する</h3><p>締切から72時間以内に、好きなボケを最大3つ選んで発表。結果ページはそのまま残ります。</p></div></li>
</ol>
<div class="p-about__answerer"><span class="c-tag c-tag--ink c-tag--small">回答する人</span><p>募集URLを開いて、ボケを書くだけ。回答すると、ほかの人のボケが見られます。</p></div>
</section>

<section class="l-section">
<div class="p-about__none">
<h2 class="p-about__none-title">ぼけばこに、ないもの</h2>
<ul class="p-about__none-list">
<li><span class="p-about__x" aria-hidden="true">✕</span><s>いいね・投票</s></li>
<li><span class="p-about__x" aria-hidden="true">✕</span><s>ランキング</s></li>
<li><span class="p-about__x" aria-hidden="true">✕</span><s>会員登録・フォロー</s></li>
</ul>
<p class="p-about__none-body">評価するのは、お題を出した人だけ。数字を気にせず、思いついたボケをそのまま出せます。</p>
</div>
</section>

<section class="l-section">
<h2 class="p-about__h2">よくある質問</h2>
<div class="p-about__faq">
<details open><summary><span><em>Q</em>本当に登録なしで使えますか？</span><span class="p-about__faq-mark" aria-hidden="true">＋</span></summary><p>はい。開催も回答も、名前やメールアドレスの登録なしでできます。</p></details>
<details><summary><span><em>Q</em>1つのお題に何回でも回答できますか？</span><span class="p-about__faq-mark" aria-hidden="true">＋</span></summary><p>何回でも回答できます。ただし、短い時間に続けて投稿すると、しばらく投稿できなくなることがあります。</p></details>
<details><summary><span><em>Q</em>別のスマホから開催者ページを開くには？</span><span class="p-about__faq-mark" aria-hidden="true">＋</span></summary><p>募集URLと、開催するときに決めた合言葉を入力すると開けます。</p></details>
<details><summary><span><em>Q</em>結果を選ばなかったらどうなりますか？</span><span class="p-about__faq-mark" aria-hidden="true">＋</span></summary><p>締切から72時間たつと「選出なし」で終了し、すべての回答が公開されます。</p></details>
<details><summary><span><em>Q</em>投稿を消してほしいときは？</span><span class="p-about__faq-mark" aria-hidden="true">＋</span></summary><p>お問い合わせから「投稿削除依頼」を選び、対象の大喜利のURLを添えてご連絡ください。</p></details>
</div>
</section>

<section class="l-section">
<div class="c-card">
<h2 class="p-about__h3">こんな投稿は禁止です</h2>
<ul class="p-about__rules">
<li>実在する人への攻撃や、いじめに使うこと</li>
<li>名前・住所などの個人情報</li>
<li>差別、脅迫、過度に性的な内容</li>
<li>他人の作品や権利を侵害するもの</li>
</ul>
<a class="p-about__link" href="<?= site_url('guidelines') ?>">投稿ガイドラインを読む</a>
</div>
</section>

<section class="l-section p-about__last">
<a class="c-button c-button--primary" href="<?= site_url('/') ?>">さっそく大喜利を開催する</a>
</section>
<?= $this->endSection() ?>
