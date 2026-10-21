<?php

use App\Entities\Answer;

/**
 * A-04 回答管理
 *
 * @var string                                                                                                      $screenId
 * @var string                                                                                                      $title
 * @var array{q: string, oogiri: string, status: string, reports: string, period: string, from: string, to: string} $filter 絞り込みの現在値
 * @var array<string, string>                                                                                       $statuses      状態の選択肢（値 => 表示名）
 * @var array<string, string>                                                                                       $reportFilters 通報数の選択肢
 * @var array<string, string>                                                                                       $periods       期間の選択肢
 * @var bool                                                                                                        $canViewSource 投稿元識別情報の列を出すか（上位管理者だけ true）
 * @var list<array{answer: Answer, posted: string, alert: bool, visible: bool}>                                     $items 一覧の行
 * @var array{label: string, current: int, links: list<array{page: int, url: string}|null>}                         $pager
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a04-answers.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form class="c-filterbar" method="get" action="<?= admin_url('answers') ?>">
<input class="c-ainput p-answers__q" type="text" name="q" aria-label="キーワード" placeholder="本文で検索" value="<?= esc($filter['q'], 'attr') ?>">
<input class="c-ainput p-answers__oogiri" type="text" name="oogiri" aria-label="お題" placeholder="お題（ID・スラッグ）" value="<?= esc($filter['oogiri'], 'attr') ?>">
<select class="c-aselect p-answers__status" name="status" aria-label="状態">
<?php foreach ($statuses as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"<?= $value === $filter['status'] ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<select class="c-aselect p-answers__select" name="reports" aria-label="通報数">
<?php foreach ($reportFilters as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"<?= $value === $filter['reports'] ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<select class="c-aselect p-answers__select" name="period" aria-label="期間">
<?php foreach ($periods as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"<?= $value === $filter['period'] ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<?= part('partials/admin_date_range', ['from' => $filter['from'], 'to' => $filter['to']]) ?>
<button type="submit" class="c-abutton c-abutton--dark">検索</button>
</form>

<?php if ($canViewSource): ?>
<p class="c-lock-note"><?= $this->include('partials/icon_lock') ?>「投稿元」の列は、上位管理者にだけ表示されます（ハッシュ化した値のみ）。</p>
<?php endif; ?>

<div class="c-panel c-panel--table">
<div class="c-table p-answers__table<?= $canViewSource ? ' p-answers__table--source' : '' ?>" role="table">
<div class="c-table__row c-table__row--head" role="row">
<div class="c-table__head" role="columnheader">ID</div>
<div class="c-table__head" role="columnheader">本文</div>
<div class="c-table__head" role="columnheader">お題</div>
<div class="c-table__head" role="columnheader">投稿日時</div>
<div class="c-table__head c-table__head--num" role="columnheader">通報</div>
<div class="c-table__head" role="columnheader">状態</div>
<?php if ($canViewSource): ?>
<div class="c-table__head" role="columnheader"><span class="p-answers__source-head"><?= $this->include('partials/icon_lock') ?>投稿元</span></div>
<?php endif; ?>
<div class="c-table__head" role="columnheader">操作</div>
</div>
<?php if ($items === []): ?>
<p class="c-table__empty">該当する回答はありません。</p>
<?php endif; ?>
<?php foreach ($items as $row): ?>
<?php $answer = $row['answer']; ?>
<div class="c-table__row" role="row">
<div class="c-table__cell c-table__cell--id" role="cell" data-label="ID">A-<?= esc((string) $answer->id) ?></div>
<div class="c-table__cell c-table__cell--strong" role="cell" data-label="本文"><?= nl2br_esc($answer->body) ?></div>
<div class="c-table__cell c-table__cell--sub c-table__cell--clip" role="cell" data-label="お題"><a class="p-answers__odai" href="<?= admin_url('oogiri/' . $answer->oogiri_id) ?>"><?= esc($answer->odai) ?></a></div>
<div class="c-table__cell c-table__cell--time" role="cell" data-label="投稿日時"><?= esc($row['posted']) ?></div>
<div class="c-table__cell c-table__cell--num<?= $row['alert'] ? ' c-table__cell--alert' : '' ?>" role="cell" data-label="通報"><?= esc((string) $answer->report_count) ?></div>
<div class="c-table__cell" role="cell" data-label="状態"><?= part('partials/admin_badge', ['status' => $answer->status()->value, 'label' => $answer->status()->label()]) ?></div>
<?php if ($canViewSource): ?>
<div class="c-table__cell c-table__cell--mono" role="cell" data-label="投稿元">IP <?= esc($answer->shortIpHash()) ?><br>ID <?= esc($answer->shortBrowserIdHash()) ?></div>
<?php endif; ?>
<div class="c-table__cell" role="cell">
<div class="c-table__actions">
<?php if ($row['visible']): ?>
<?= part('partials/admin_action_form', ['action' => admin_url('answers/' . $answer->id . '/hide'), 'label' => '非表示', 'variant' => 'small', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?>
<?php else: ?>
<?= part('partials/admin_action_form', ['action' => admin_url('answers/' . $answer->id . '/publish'), 'label' => '再公開', 'variant' => 'small', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?>
<?php endif; ?>
<?= part('partials/admin_action_form', ['action' => admin_url('answers/' . $answer->id . '/delete'), 'label' => '削除', 'variant' => 'icon', 'iconOnly' => true, 'confirm' => '回答 A-' . $answer->id . ' を削除しますか？', 'id' => 'delete-' . $answer->id, 'hidden' => []]) ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<?= part('partials/admin_pager', ['pager' => $pager]) ?>
<?= $this->endSection() ?>
