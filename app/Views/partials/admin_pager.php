<?php
/**
 * 管理画面のページ送り（A-03・A-04）。値は DisplayService::pager() が組み立てる
 *
 * @var array{label: string, current: int, links: list<array{page: int, url: string}|null>} $pager null は「…」
 */
?>
<div class="c-pager">
<span class="c-pager__label"><?= esc($pager['label']) ?></span>
<?php if (count($pager['links']) > 1): ?>
<nav class="c-pager__pages" aria-label="ページ">
<?php foreach ($pager['links'] as $link): ?>
<?php if ($link === null): ?>
<span class="c-pager__gap" aria-hidden="true">…</span>
<?php elseif ($link['page'] === $pager['current']): ?>
<span class="c-pager__page is-active" aria-current="page"><?= esc((string) $link['page']) ?></span>
<?php else: ?>
<a class="c-pager__page" href="<?= esc($link['url'], 'attr') ?>"><?= esc((string) $link['page']) ?></a>
<?php endif; ?>
<?php endforeach; ?>
</nav>
<?php endif; ?>
</div>
