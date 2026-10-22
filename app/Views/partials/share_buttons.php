<?php
/**
 * シェアのボタン（BUG-003、ADR 0015）
 *
 * 1. 「Xで〜」：X の投稿画面（Web Intent）。スマホでは X のアプリが開く。いちばん目立たせる
 * 2. 「ほかのアプリで共有」：OS の共有シート（Web Share API）。使えるブラウザでだけ JS が表示する（JS なしでは出ない）
 *    共有シートで送り終えたときだけ GA4 の share_complete を送る（X の投稿画面は、投稿したかどうかが分からないので送らない）
 *
 * @var string   $intentUrl X の Web Intent URL
 * @var string   $label     X のボタンの文言（例：Xで回答を募集する）
 * @var string   $track     自前の計測のイベント名（share_recruit / share_answer / share_result）
 * @var string   $slug      大喜利のスラッグ（計測用）
 * @var string   $url       共有シートに渡す URL
 * @var string   $title     共有シートに渡す題名
 * @var int|null $answerId  回答のシェアなら回答 ID（計測用）
 */
$answerId ??= null;
?>
<div class="c-share">
<a class="c-button c-button--x" href="<?= esc($intentUrl, 'attr') ?>" target="_blank" rel="noopener" data-js-track="<?= esc($track, 'attr') ?>" data-js-track-oogiri="<?= esc($slug, 'attr') ?>"<?= $answerId !== null ? ' data-js-track-answer="' . $answerId . '"' : '' ?>><?= $this->include('partials/icon_share') ?><?= esc($label) ?></a>
<button type="button" class="c-button c-button--secondary c-share__native" data-js-web-share data-js-share-url="<?= esc($url, 'attr') ?>" data-js-share-title="<?= esc($title, 'attr') ?>" data-js-share-type="<?= esc(str_replace('share_', '', $track), 'attr') ?>" data-js-share-oogiri="<?= esc($slug, 'attr') ?>" hidden>ほかのアプリで共有</button>
</div>
