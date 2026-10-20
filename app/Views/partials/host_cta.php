<?php
/**
 * 「自分も大喜利を開催する」導線（派生元を引き継ぐ：S-06）
 *
 * @var string $slug   派生元の大喜利
 * @var string $from   計測用の遷移元画面（例：U-06）
 * @var string $label  省略時「自分も大喜利を開催する」
 */
$label ??= '自分も大喜利を開催する';
?>
<a class="c-button c-button--primary" href="<?= site_url('/?from=' . rawurlencode($slug)) ?>" data-js-track="click_create" data-js-track-oogiri="<?= esc($slug, 'attr') ?>" data-js-track-from="<?= esc($from, 'attr') ?>"><?= esc($label) ?></a>
