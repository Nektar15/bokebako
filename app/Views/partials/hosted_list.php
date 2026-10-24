<?php
/**
 * このブラウザで開催した大喜利（D-02、ADR 0015）。中身は JS が localStorage の開催者トークンで取りに行く。なければ出さない
 * 本人のブラウザにだけ出る一覧で、公開のプロフィールではない。
 *
 * @var string $except 一覧から外すスラッグ（開催者ページで、いま開いている大喜利）。省略可
 */
$except ??= '';
?>
<section class="l-section c-hosted" data-js-hosted data-js-hosted-except="<?= esc($except, 'attr') ?>" hidden>
<h2 class="c-hosted__title">あなたが開催した大喜利</h2>
<p class="u-sub">このブラウザで開催したものだけが出ます。ほかの人には見えません。</p>
<ul class="c-hosted__list" data-js-hosted-list></ul>
</section>
