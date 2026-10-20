<?php
/**
 * 管理画面の絞り込みタブ（丸いボタンの並び。A-03・A-05・A-09）。リンクなので JS なしで切り替わる
 *
 * @var list<array{label: string, count: string|null, url: string, active: bool}> $tabs count は整形済みの文字列（例：「12,480」）
 * @var string $ariaLabel
 */
?>
<nav class="c-pills" aria-label="<?= esc($ariaLabel, 'attr') ?>">
<?php foreach ($tabs as $tab): ?>
<a class="c-pills__item<?= $tab['active'] ? ' is-active' : '' ?>" href="<?= esc($tab['url'], 'attr') ?>"<?= $tab['active'] ? ' aria-current="page"' : '' ?>><?= esc($tab['label']) ?><?php if ($tab['count'] !== null): ?> <span class="c-pills__count"><?= esc($tab['count']) ?></span><?php endif; ?></a>
<?php endforeach; ?>
</nav>
