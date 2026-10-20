<?php
/**
 * 残り時間とバー（U-04 / U-07）。JS（pages/answer.js）が1分ごとに更新する
 *
 * @var string $slug
 * @var string $remaining       例：「18時間32分」
 * @var int    $progressPercent 0〜100
 * @var bool   $compact         見出し行を小さくする（U-07）。省略時 false
 */
$compact ??= false;
?>
<div class="c-timer<?= $compact ? ' c-timer--compact' : '' ?>" data-js-timer="<?= esc($slug, 'attr') ?>">
<div class="c-timer__head">
<span class="c-timer__label">締切まで</span>
<span class="c-timer__value">残り <span data-js-timer-remaining><?= esc($remaining) ?></span></span>
</div>
<progress class="c-progress" data-js-timer-bar value="<?= (int) $progressPercent ?>" max="100" aria-label="残り時間の割合"></progress>
</div>
