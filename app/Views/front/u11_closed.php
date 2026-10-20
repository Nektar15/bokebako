<?php
/**
 * U-11 募集終了ページ（結果選択待ち）
 * 結果確定済みは URL の振り分けで U-10 に直接行くため、ここは「主催者が選んでいます」だけを出す（未確定事項6）
 *
 * @var \App\Entities\Oogiri $oogiri
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>募集は終了しました：<?= esc($oogiri->odai) ?>｜ぼけばこ</title>
<meta property="og:title" content="<?= esc($oogiri->odai, 'attr') ?>">
<meta property="og:description" content="募集は終了しました。主催者がベスト回答を選んでいます。">
<meta property="og:image" content="<?= esc(base_url('ogp/recruit/' . $oogiri->slug . '.png'), 'attr') ?>">
<meta name="twitter:card" content="summary_large_image">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/u11-closed.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="l-section p-closed__hero">
<svg width="132" height="104" viewBox="0 0 132 104" aria-hidden="true">
<rect x="14" y="36" width="104" height="60" rx="8" fill="#D6401E" stroke="#1D1A16" stroke-width="3"></rect>
<path d="M10 36 L66 18 L122 36" fill="#E6DDCC" stroke="#1D1A16" stroke-width="3" stroke-linejoin="round"></path>
<rect x="44" y="52" width="44" height="30" rx="4" fill="#FFFDF8" stroke="#1D1A16" stroke-width="3" transform="rotate(-6 66 67)"></rect>
<text x="66" y="74" text-anchor="middle" font-family="Dela Gothic One, sans-serif" font-size="18" fill="#1D1A16" transform="rotate(-6 66 67)">〆切</text>
</svg>
<h1 class="c-heading c-heading--sm">募集は終了しました</h1>
<p class="p-closed__lead">いま、主催者がベスト回答を選んでいます。<br>結果発表まで、もうしばらくお待ちください。</p>
</section>

<section class="l-section">
<?= part('partials/odai_card', ['odai' => $oogiri->odai, 'tone' => 'sand', 'raised' => false, 'extra' => '<p class="u-sub"><strong>' . esc((string) $oogiri->answer_count) . '件</strong>のボケが集まりました</p>']) ?>
</section>

<section class="l-section p-closed__cta">
<p class="p-closed__cta-text">待っている間に、あなたも1本どうですか。</p>
<?= part('partials/host_cta', ['slug' => $oogiri->slug, 'from' => 'U-11']) ?>
</section>
<?= $this->endSection() ?>
