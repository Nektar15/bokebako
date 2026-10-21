<?php
/**
 * 期間指定の開始日・終了日（A-03・A-04・A-09 の絞り込み）。どちらかを入れると「期間指定」で絞り込む
 *
 * @var string $from YYYY-MM-DD か空
 * @var string $to   YYYY-MM-DD か空
 */
?>
<span class="c-date-range">
<input class="c-ainput c-date-range__input" type="date" name="from" value="<?= esc($from, 'attr') ?>" aria-label="開始日">
<span class="c-date-range__sep" aria-hidden="true">〜</span>
<input class="c-ainput c-date-range__input" type="date" name="to" value="<?= esc($to, 'attr') ?>" aria-label="終了日">
</span>
