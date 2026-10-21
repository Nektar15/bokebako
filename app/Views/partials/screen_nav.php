<?php
/**
 * 画面一覧ナビ（確認用。ADR 0009）。screenNav.enabled のときだけ画面の最上部に出す。本番では出ない
 *
 * 値は受け取らない（screen_nav() ヘルパーから読む）。JS があれば選んだ時点で移動、なければ「移動」ボタンで送る。
 */
$nav = screen_nav();
?>
<?php if ($nav !== null): ?>
<form class="c-screen-nav" method="get" action="<?= esc($nav['action'], 'attr') ?>" data-js-screen-nav>
<span class="c-screen-nav__tag">確認用</span>
<label class="u-visually-hidden" for="screen-nav-to">画面を選んで移動</label>
<select class="c-screen-nav__select" id="screen-nav-to" name="to" data-js-screen-nav-select>
<option value="" selected disabled>全画面へ移動…</option>
<?php foreach ($nav['groups'] as $group): ?>
<optgroup label="<?= esc($group['label'], 'attr') ?>">
<?php foreach ($group['options'] as $option): ?>
<option value="<?= esc((string) $option['index'], 'attr') ?>"><?= esc($option['label']) ?><?= $option['note'] !== '' ? '（' . esc($option['note']) . '）' : '' ?></option>
<?php endforeach; ?>
</optgroup>
<?php endforeach; ?>
</select>
<button type="submit" class="c-screen-nav__go" data-js-hide-with-js>移動</button>
</form>
<?php endif; ?>
