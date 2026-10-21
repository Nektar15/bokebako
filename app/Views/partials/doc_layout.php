<?php
/**
 * 規約類（U-15 / U-16）の共通の組み方：見出し・目次・本文
 * 本文は別の View（$articlesView）に書き、ここで include する。
 *
 * @var string                               $title
 * @var string                               $updatedAt  最終更新日の表示（例：「2026年10月20日」）
 * @var list<array{id:string, title:string}> $toc
 * @var string                               $articlesView 本文の View 名（例：front/u15_terms_body）
 * @var bool                                 $showDraftNote 本文 View に渡す
 */
?>
<section class="l-section p-doc__head">
<span class="c-tag c-tag--sand">ご利用の前に</span>
<h1 class="c-heading"><?= esc($title) ?></h1>
<p class="u-sub">最終更新日：<?= esc($updatedAt) ?></p>
</section>
<nav class="l-section" aria-label="目次">
<div class="c-card p-doc__toc">
<p class="p-doc__toc-title">目次</p>
<ol>
<?php foreach ($toc as $item): ?><li><a href="#<?= esc($item['id'], 'attr') ?>"><?= esc($item['title']) ?></a></li><?php endforeach; ?>
</ol>
</div>
</nav>
<div class="l-section p-doc__body">
<?= part($articlesView, ['showDraftNote' => $showDraftNote]) ?>
<a class="p-doc__top" href="#">ページの先頭へ</a>
</div>
