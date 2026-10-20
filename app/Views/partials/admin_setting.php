<?php
/**
 * 運用設定（A-10）の1行：左に項目名と説明、右に入力欄
 *
 * @var string $type    入力の種類：number（数値1つ）/ pair（数値2つ）/ select / switch / checks / textarea / text
 * @var string $name    項目名
 * @var string $desc    説明
 * @var string $default 「初期値 …」の表示（例：「100文字」）
 * @var list<array{key: string, value: string, unit: string, width: string}> $inputs number / pair の入力欄（unit は後ろに出す単位、width は 'sm' | 'md' | 'lg'）
 * @var string $key     select / switch / checks / textarea / text の name 属性
 * @var string $value   select / textarea / text の現在値
 * @var bool   $checked switch の現在値
 * @var array<string, string> $options select / checks の選択肢（値 => 表示名）
 * @var array<string, bool>    $selected checks の現在値（値 => チェックされているか）
 * @var string $onLabel  switch が ON のときの表示（例：「ON」）
 * @var string $offLabel switch が OFF のときの表示
 * @var string $extra    入力欄の横に添える補足（例：「今月 1,284件 使用」）。なければ空
 * @var string $error    入力エラーの文言。なければ空（inputs / extra / error も省略不可。部品の値は呼び出し間で残るため、毎回すべて渡す）
 */
?>
<div class="c-setting">
<div class="c-setting__head"><span class="c-setting__name"><?= esc($name) ?></span><span class="c-setting__desc"><?= esc($desc) ?></span></div>
<div class="c-setting__body">
<div class="c-afield__inline">
<?php if ($type === 'number' || $type === 'pair'): ?>
<?php foreach ($inputs as $i => $input): ?>
<?php if ($i > 0): ?><span class="c-afield__unit">／</span><?php endif; ?>
<input class="c-ainput c-ainput--num p-settings__num p-settings__num--<?= esc($input['width'], 'attr') ?><?= $error !== '' ? ' is-invalid' : '' ?>" type="number" name="<?= esc($input['key'], 'attr') ?>" aria-label="<?= esc($name, 'attr') ?>" value="<?= esc($input['value'], 'attr') ?>" required>
<span class="c-afield__unit"><?= esc($input['unit']) ?></span>
<?php endforeach; ?>
<?php elseif ($type === 'select'): ?>
<select class="c-aselect p-settings__select" name="<?= esc($key, 'attr') ?>" aria-label="<?= esc($name, 'attr') ?>">
<?php foreach ($options as $optionValue => $label): ?>
<option value="<?= esc($optionValue, 'attr') ?>"<?= $optionValue === $value ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<?php elseif ($type === 'switch'): ?>
<label class="c-switch"><input class="c-switch__input" type="checkbox" name="<?= esc($key, 'attr') ?>" value="1"<?= $checked ? ' checked' : '' ?> data-js-switch-on="<?= esc($onLabel, 'attr') ?>" data-js-switch-off="<?= esc($offLabel, 'attr') ?>"><span class="c-switch__track" aria-hidden="true"></span><span class="c-switch__label" data-js-switch-label><?= esc($checked ? $onLabel : $offLabel) ?></span></label>
<?php elseif ($type === 'checks'): ?>
<div class="c-checks">
<?php foreach ($options as $optionValue => $label): ?>
<label class="c-checks__item"><input type="checkbox" name="<?= esc($key, 'attr') ?>[]" value="<?= esc($optionValue, 'attr') ?>"<?= ($selected[$optionValue] ?? false) ? ' checked' : '' ?>><?= esc($label) ?></label>
<?php endforeach; ?>
</div>
<?php elseif ($type === 'textarea'): ?>
<textarea class="c-atextarea p-settings__tpl<?= $error !== '' ? ' is-invalid' : '' ?>" name="<?= esc($key, 'attr') ?>" aria-label="<?= esc($name, 'attr') ?>" rows="3"><?= esc($value) ?></textarea>
<?php else: ?>
<span class="p-settings__hash">#</span>
<input class="c-ainput p-settings__text<?= $error !== '' ? ' is-invalid' : '' ?>" type="text" name="<?= esc($key, 'attr') ?>" aria-label="<?= esc($name, 'attr') ?>" value="<?= esc($value, 'attr') ?>" required>
<?php endif; ?>
<?php if ($extra !== ''): ?><span class="c-afield__help"><?= esc($extra) ?></span><?php endif; ?>
<span class="c-afield__default">初期値 <?= esc($default) ?></span>
</div>
<?php if ($error !== ''): ?><p class="c-afield__error"><?= esc($error) ?></p><?php endif; ?>
</div>
</div>
