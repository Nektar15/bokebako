<?php
/**
 * 画面一覧ナビ（確認用。ADR 0009）。screenNav.enabled のときだけ、画面の右下に「全画面」ボタンを出す。本番では出ない
 *
 * 値は受け取らない（screen_nav() ヘルパーから読む）。開け閉めは <details>、移動はふつうのリンクなので JS がなくても動く。
 * 開いている間は <summary> が右上の「×」になる（JS なしでも閉じられる）。JS があれば、背景のクリックと Esc でも閉じる（components/screen-nav.js）。
 */
$nav = screen_nav();
?>
<?php if ($nav !== null): ?>
<details class="c-screen-nav" data-js-screen-nav>
<summary class="c-screen-nav__toggle" aria-label="全画面へ移動"><span class="c-screen-nav__open"><span class="c-screen-nav__tag">確認用</span>全画面</span><span class="c-screen-nav__close" aria-hidden="true">×</span></summary>
<div class="c-screen-nav__backdrop" data-js-screen-nav-close></div>
<div class="c-screen-nav__panel" role="dialog" aria-label="全画面へ移動">
<div class="c-screen-nav__head">
<p class="c-screen-nav__title">全画面へ移動</p>
<p class="c-screen-nav__sub">開発用データの大喜利で開きます。24時間たったあとの画面もあります</p>
</div>
<div class="c-screen-nav__body">
<?php foreach ($nav['groups'] as $group): ?>
<section class="c-screen-nav__group">
<h2 class="c-screen-nav__group-title"><?= esc($group['label']) ?></h2>
<ul class="c-screen-nav__list">
<?php foreach ($group['options'] as $option): ?>
<li><a class="c-screen-nav__link" href="<?= esc($option['href'], 'attr') ?>"><span class="c-screen-nav__id"><?= esc($option['id'] !== '' ? $option['id'] : '—') ?></span><span class="c-screen-nav__text"><span class="c-screen-nav__label"><?= esc($option['name']) ?></span><?php if ($option['note'] !== ''): ?><span class="c-screen-nav__note"><?= esc($option['note']) ?></span><?php endif; ?></span></a></li>
<?php endforeach; ?>
</ul>
</section>
<?php endforeach; ?>
</div>
</div>
</details>
<?php endif; ?>
