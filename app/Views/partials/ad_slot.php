<?php
/**
 * 広告枠（上部・下部）。広告がないときは枠ごと出さない（FEATURES 0-1）
 *
 * @var array{id:int,image:string,alt:string}|null $ad
 * @var string $position  top | bottom
 */
if (empty($ad)) {
    return;
}
?>
<div class="c-ad c-ad--<?= esc($position, 'attr') ?>">
<a class="c-ad__link" href="<?= site_url('ad/' . $ad['id'] . '/click') ?>" rel="sponsored nofollow" target="_blank">
<span class="c-ad__pr">PR</span>
<img class="c-ad__image" src="<?= esc($ad['image'], 'attr') ?>" alt="<?= esc($ad['alt'], 'attr') ?>" width="320" height="50">
</a>
</div>
