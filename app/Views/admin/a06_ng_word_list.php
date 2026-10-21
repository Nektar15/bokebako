<?php

use App\Entities\NgWord;
use App\Enums\NgWordAction;
use App\Enums\NgWordMatchType;

/**
 * A-06 NGワード管理
 *
 * @var string                                                                    $screenId
 * @var string                                                                    $title
 * @var array{q: string, action: string, category: string}                        $filter 絞り込みの現在値
 * @var array<string, string>                                                     $actions    処理の選択肢（'all' を含む。値 => 表示名）
 * @var list<string>                                                              $categories カテゴリーの一覧
 * @var array{enabled: int, total: int}                                           $counts 有効な件数と全件数
 * @var list<NgWordMatchType>                                                     $matchTypes
 * @var list<NgWordAction>                                                        $actionCases
 * @var list<array{word: NgWord, updated: string}>                                $items 一覧の行
 * @var NgWord|null                                                               $editing 編集中のワード（追加なら null）
 * @var array{word: string, match_type: string, category: string, action: string} $form フォームの初期値
 * @var string                                                                    $csvColumns CSV の列名（表示用）
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a06-ng-words.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('actions') ?>
<a class="c-abutton" href="<?= admin_url('ng-words/export') ?>">CSVエクスポート</a>
<form class="c-action" method="post" action="<?= admin_url('ng-words/import') ?>" enctype="multipart/form-data" data-js-once>
<?= csrf_field() ?>
<label class="c-abutton p-ng__import">CSVインポート<input class="c-upload__input" type="file" name="csv" accept=".csv,text/csv" required data-js-autosubmit></label>
<button type="submit" class="c-abutton c-abutton--small p-ng__import-submit" data-js-hide-with-js>取り込む</button>
</form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-ng">
<div class="p-ng__main">

<form class="c-filterbar c-filterbar--slim" method="get" action="<?= admin_url('ng-words') ?>">
<input class="c-ainput p-ng__q" type="text" name="q" aria-label="キーワード" placeholder="ワードで検索" value="<?= esc($filter['q'], 'attr') ?>">
<select class="c-aselect p-ng__action" name="action" aria-label="処理" data-js-autosubmit>
<?php foreach ($actions as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"<?= $value === $filter['action'] ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<select class="c-aselect p-ng__category" name="category" aria-label="カテゴリー" data-js-autosubmit>
<option value="all"<?= $filter['category'] === 'all' ? ' selected' : '' ?>>カテゴリー：すべて</option>
<?php foreach ($categories as $category): ?>
<option value="<?= esc($category, 'attr') ?>"<?= $category === $filter['category'] ? ' selected' : '' ?>><?= esc($category) ?></option>
<?php endforeach; ?>
</select>
<button type="submit" class="c-abutton c-abutton--small" data-js-hide-with-js>絞り込む</button>
<span class="c-filterbar__count">有効 <?= esc((string) $counts['enabled']) ?> ／ 全 <?= esc((string) $counts['total']) ?>件</span>
</form>

<div class="c-panel c-panel--table">
<div class="c-table p-ng__table" role="table">
<div class="c-table__row c-table__row--head" role="row">
<div class="c-table__head" role="columnheader">ワード</div>
<div class="c-table__head" role="columnheader">一致方式</div>
<div class="c-table__head" role="columnheader">カテゴリー</div>
<div class="c-table__head" role="columnheader">処理</div>
<div class="c-table__head" role="columnheader">有効</div>
<div class="c-table__head" role="columnheader">更新日</div>
<div class="c-table__head" role="columnheader">操作</div>
</div>
<?php if ($items === []): ?>
<p class="c-table__empty">該当するワードはありません。</p>
<?php endif; ?>
<?php foreach ($items as $row): ?>
<?php $word = $row['word']; ?>
<div class="c-table__row<?= $word->enabled ? '' : ' c-table__row--muted' ?>" role="row">
<div class="c-table__cell c-table__cell--break" role="cell" data-label="ワード"><?= esc($word->word) ?></div>
<div class="c-table__cell c-table__cell--sub" role="cell" data-label="一致方式"><?= esc($word->matchType()->label()) ?></div>
<div class="c-table__cell c-table__cell--sub" role="cell" data-label="カテゴリー"><?= esc($word->category) ?></div>
<div class="c-table__cell" role="cell" data-label="処理"><?= part('partials/admin_badge', ['status' => $word->action()->value, 'label' => $word->action()->label()]) ?></div>
<div class="c-table__cell" role="cell" data-label="有効">
<form class="c-action" method="post" action="<?= admin_url('ng-words/' . $word->id) ?>" data-js-once>
<?= csrf_field() ?>
<input type="hidden" name="word" value="<?= esc($word->word, 'attr') ?>">
<input type="hidden" name="match_type" value="<?= esc($word->match_type, 'attr') ?>">
<input type="hidden" name="category" value="<?= esc($word->category, 'attr') ?>">
<input type="hidden" name="action" value="<?= esc($word->action, 'attr') ?>">
<input type="hidden" name="enabled" value="<?= $word->enabled ? '0' : '1' ?>">
<button type="submit" class="c-switch c-switch--small c-switch--button" role="switch" aria-checked="<?= $word->enabled ? 'true' : 'false' ?>" aria-label="有効"><span class="c-switch__track"></span></button>
</form>
</div>
<div class="c-table__cell c-table__cell--time" role="cell" data-label="更新日"><?= esc($row['updated']) ?></div>
<div class="c-table__cell" role="cell">
<div class="c-table__actions">
<a class="c-abutton c-abutton--tiny" href="<?= admin_url('ng-words?edit=' . $word->id) ?>">編集</a>
<?= part('partials/admin_action_form', ['action' => admin_url('ng-words/' . $word->id . '/delete'), 'label' => '削除', 'variant' => 'icon-tiny', 'iconOnly' => true, 'confirm' => '「' . $word->word . '」を削除しますか？', 'id' => 'delete-' . $word->id, 'hidden' => []]) ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
<p class="c-panel__note">CSVの列：<?= esc($csvColumns) ?>（match_type は exact / partial / regex、action は reject / review、enabled は 1 / 0）。インポートは同じワード・一致方式の行を上書きします。</p>

</div>

<form class="c-panel c-panel--raised p-ng__form" method="post" action="<?= $editing !== null ? admin_url('ng-words/' . $editing->id) : admin_url('ng-words') ?>" data-js-once>
<?= csrf_field() ?>
<input type="hidden" name="enabled" value="<?= $editing !== null && ! $editing->enabled ? '0' : '1' ?>">
<h2 class="c-panel__title c-panel__title--lg"><?= $editing !== null ? 'ワードを編集' : 'ワードを追加' ?></h2>
<div class="c-afield">
<label class="c-afield__label" for="ng-word">ワード</label>
<input class="c-ainput c-ainput--full" id="ng-word" name="word" type="text" placeholder="例：住所" required value="<?= esc($form['word'], 'attr') ?>">
</div>
<div class="c-afield">
<span class="c-afield__label" id="ng-match-label">一致方式</span>
<div class="c-segment" role="group" aria-labelledby="ng-match-label" data-js-ng-match>
<?php foreach ($matchTypes as $type): ?>
<span class="c-segment__item"><input class="c-segment__input" type="radio" name="match_type" id="ng-match-<?= esc($type->value, 'attr') ?>" value="<?= esc($type->value, 'attr') ?>" data-js-help="<?= esc($type->help(), 'attr') ?>"<?= $type->value === $form['match_type'] ? ' checked' : '' ?>><label class="c-segment__label" for="ng-match-<?= esc($type->value, 'attr') ?>"><?= esc($type->label()) ?></label></span>
<?php endforeach; ?>
</div>
<p class="c-afield__help" data-js-ng-match-help>
<?php foreach ($matchTypes as $type): ?>
<?php if ($type->value === $form['match_type']): ?><?= esc($type->help()) ?><?php endif; ?>
<?php endforeach; ?>
</p>
</div>
<div class="c-afield">
<label class="c-afield__label" for="ng-category">カテゴリー</label>
<select class="c-aselect c-aselect--full" id="ng-category" name="category">
<?php foreach ($categories as $category): ?>
<option value="<?= esc($category, 'attr') ?>"<?= $category === $form['category'] ? ' selected' : '' ?>><?= esc($category) ?></option>
<?php endforeach; ?>
</select>
</div>
<fieldset class="c-afield">
<legend class="c-afield__label">一致したときの処理</legend>
<div class="c-choicecard">
<?php foreach ($actionCases as $action): ?>
<label class="c-choicecard__item"><input class="c-choicecard__input" type="radio" name="action" value="<?= esc($action->value, 'attr') ?>"<?= $action->value === $form['action'] ? ' checked' : '' ?>><span class="c-choicecard__title"><?= esc($action->label()) ?></span><span class="c-choicecard__desc"><?= esc($action->description()) ?></span></label>
<?php endforeach; ?>
</div>
</fieldset>
<div class="p-ng__try">
<label class="c-afield__label" for="ng-try">ためしに判定する</label>
<input class="c-ainput c-ainput--full" id="ng-try" type="text" placeholder="判定したい文章を入力" data-js-ng-try>
<p class="c-afield__help" data-js-ng-try-result>保存前に、このワードで一致するかを確認できます。</p>
</div>
<button type="submit" class="c-abutton c-abutton--primary c-abutton--tall"><?= $editing !== null ? '保存する' : '追加する' ?></button>
<?php if ($editing !== null): ?>
<a class="c-abutton c-abutton--link" href="<?= admin_url('ng-words') ?>">追加に戻る</a>
<?php endif; ?>
</form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= asset_url('js/pages/ng-words.js') ?>"></script>
<?= $this->endSection() ?>
