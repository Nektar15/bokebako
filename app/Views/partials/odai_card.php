<?php
/**
 * お題カード（付箋タブ付き）
 *
 * @var string $odai
 * @var string $tone      kin（募集中）| sand（終了）。省略時 kin
 * @var string $size      lg（回答画面の見出し）| md。省略時 md
 * @var bool   $raised    影を付けるか。省略時 true
 * @var string $extra     カード内の追加HTML（整形済み・エスケープ済み）。省略可
 */
$tone ??= 'kin';
$size ??= 'md';
$raised ??= true;
$extra ??= '';
?>
<div class="c-card c-card--odai<?= $raised ? ' c-card--raised' : '' ?>">
<span class="c-card__tab<?= $tone === 'sand' ? ' c-card__tab--sand' : '' ?>">お題</span>
<?php if ($size === 'lg'): ?>
<h1 class="c-heading"><?= esc($odai) ?></h1>
<?php else: ?>
<p class="c-odai"><?= esc($odai) ?></p>
<?php endif; ?>
<?= $extra ?>
</div>
