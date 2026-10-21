<?php
/**
 * 管理画面の操作ボタン（1ボタン＝1フォームの POST。非表示・再公開・削除・処理済みなど）
 *
 * confirm を渡すと確認ダイアログを挟む（JS が動かなければそのまま送信される）。
 *
 * @var string                $action   POST 先の URL
 * @var string                $label    ボタンの文字（アイコンだけのときは aria-label になる）
 * @var string                $variant  ボタンの見た目：'outline' | 'dark' | 'danger' | 'small' | 'icon' | 'icon-tiny'（icon はゴミ箱だけのボタン）
 * @var bool                  $iconOnly 文字を出さずゴミ箱アイコンだけにする（variant が icon のとき true）
 * @var string|null           $confirm  確認ダイアログの文言。null なら確認なし（省略不可。部品の値は呼び出し間で残るため、毎回すべて渡す）
 * @var string                $id       確認ダイアログの id（confirm を渡すとき必須。画面内で一意）
 * @var array<string, string> $hidden 一緒に送る hidden 項目（名前 => 値）
 */
?>
<form class="c-action" method="post" action="<?= esc($action, 'attr') ?>" data-js-once>
<?= csrf_field() ?>
<?php foreach ($hidden as $name => $value): ?>
<input type="hidden" name="<?= esc($name, 'attr') ?>" value="<?= esc($value, 'attr') ?>">
<?php endforeach; ?>
<button type="submit" class="c-abutton c-abutton--<?= esc($variant, 'attr') ?>"<?= $iconOnly ? ' aria-label="' . esc($label, 'attr') . '"' : '' ?><?= $confirm !== null ? ' data-js-open-dialog="' . esc($id, 'attr') . '"' : '' ?>>
<?php if ($iconOnly): ?><?= $this->include('partials/icon_trash') ?><?php else: ?><?= esc($label) ?><?php endif; ?>
</button>
<?php if ($confirm !== null): ?>
<dialog class="c-confirm" id="<?= esc($id, 'attr') ?>" aria-labelledby="<?= esc($id, 'attr') ?>-title">
<div class="c-confirm__body">
<h2 class="c-confirm__title" id="<?= esc($id, 'attr') ?>-title"><?= esc($confirm) ?></h2>
<p class="c-confirm__note">この操作は操作履歴に記録されます。</p>
<div class="c-confirm__actions">
<button type="button" class="c-abutton c-abutton--outline" data-js-close-dialog>やめる</button>
<button type="submit" class="c-abutton c-abutton--<?= $variant === 'dark' ? 'dark' : 'danger' ?>"><?= esc($label) ?></button>
</div>
</div>
</dialog>
<?php endif; ?>
</form>
