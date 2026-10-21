<?php
/**
 * A-03 大喜利管理（一覧）
 *
 * @var string $screenId
 * @var string $title
 * @var array{q: string, period: string, sort: string, status: string} $filter 絞り込みの現在値
 * @var array<string, string> $periods 期間の選択肢（値 => 表示名）
 * @var array<string, string> $sorts   並び替えの選択肢（値 => 表示名）
 * @var list<array{label: string, count: string|null, url: string, active: bool}> $tabs 状態タブ
 * @var list<array{oogiri: \App\Entities\Oogiri, created: string, ends: string, pv: string, alert: bool, canPublish: bool}> $items 一覧の行（日時・数値は整形済み。alert は通報が多い行、canPublish は再公開できる状態）
 * @var array{label: string, current: int, links: list<array{page: int, url: string}|null>} $pager
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a03-oogiri.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form class="c-filterbar" method="get" action="<?= admin_url('oogiri') ?>">
<input type="hidden" name="status" value="<?= esc($filter['status'], 'attr') ?>">
<input class="c-ainput p-oogiri__q" type="text" name="q" aria-label="キーワード" placeholder="お題・IDで検索" value="<?= esc($filter['q'], 'attr') ?>">
<select class="c-aselect p-oogiri__select" name="period" aria-label="期間">
<?php foreach ($periods as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"<?= $value === $filter['period'] ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<select class="c-aselect p-oogiri__select" name="sort" aria-label="並び替え">
<?php foreach ($sorts as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"<?= $value === $filter['sort'] ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<button type="submit" class="c-abutton c-abutton--dark">検索</button>
</form>

<?= part('partials/admin_tabs', ['tabs' => $tabs, 'ariaLabel' => '状態']) ?>

<div class="c-panel c-panel--table">
<div class="c-table p-oogiri__table" role="table">
<div class="c-table__row c-table__row--head" role="row">
<div class="c-table__head" role="columnheader">ID</div>
<div class="c-table__head" role="columnheader">お題</div>
<div class="c-table__head" role="columnheader">作成</div>
<div class="c-table__head" role="columnheader">終了</div>
<div class="c-table__head" role="columnheader">状態</div>
<div class="c-table__head c-table__head--num" role="columnheader">PV</div>
<div class="c-table__head c-table__head--num" role="columnheader">回答</div>
<div class="c-table__head c-table__head--num" role="columnheader">シェア</div>
<div class="c-table__head c-table__head--num" role="columnheader">派生</div>
<div class="c-table__head c-table__head--num" role="columnheader">通報</div>
<div class="c-table__head" role="columnheader">操作</div>
</div>
<?php if ($items === []): ?>
<p class="c-table__empty">該当する大喜利はありません。</p>
<?php endif; ?>
<?php foreach ($items as $row): ?>
<?php $oogiri = $row['oogiri']; ?>
<div class="c-table__row" role="row">
<div class="c-table__cell c-table__cell--id" role="cell" data-label="ID">#<?= esc((string) $oogiri->id) ?></div>
<div class="c-table__cell c-table__cell--strong c-table__cell--clip" role="cell" data-label="お題"><a class="p-oogiri__odai" href="<?= admin_url('oogiri/' . $oogiri->id) ?>"><?= esc($oogiri->odai) ?></a></div>
<div class="c-table__cell c-table__cell--time" role="cell" data-label="作成"><?= esc($row['created']) ?></div>
<div class="c-table__cell c-table__cell--time" role="cell" data-label="終了"><?= esc($row['ends']) ?></div>
<div class="c-table__cell" role="cell" data-label="状態"><?= part('partials/admin_badge', ['status' => $oogiri->status()->value, 'label' => $oogiri->status()->label()]) ?></div>
<div class="c-table__cell c-table__cell--num" role="cell" data-label="PV"><?= esc($row['pv']) ?></div>
<div class="c-table__cell c-table__cell--num" role="cell" data-label="回答"><?= esc((string) $oogiri->answer_count) ?></div>
<div class="c-table__cell c-table__cell--num" role="cell" data-label="シェア"><?= esc((string) $oogiri->share_count) ?></div>
<div class="c-table__cell c-table__cell--num" role="cell" data-label="派生"><?= esc((string) $oogiri->derived_count) ?></div>
<div class="c-table__cell c-table__cell--num<?= $row['alert'] ? ' c-table__cell--alert' : '' ?>" role="cell" data-label="通報"><?= esc((string) $oogiri->report_count) ?></div>
<div class="c-table__cell" role="cell">
<div class="c-table__actions">
<a class="c-abutton c-abutton--text" href="<?= admin_url('oogiri/' . $oogiri->id) ?>">詳細</a>
<?php if ($row['canPublish']): ?>
<?= part('partials/admin_action_form', ['action' => admin_url('oogiri/' . $oogiri->id . '/publish'), 'label' => '再公開', 'variant' => 'small', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?>
<?php else: ?>
<?= part('partials/admin_action_form', ['action' => admin_url('oogiri/' . $oogiri->id . '/hide'), 'label' => '非表示', 'variant' => 'small', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?>
<?php endif; ?>
<?= part('partials/admin_action_form', ['action' => admin_url('oogiri/' . $oogiri->id . '/delete'), 'label' => '削除', 'variant' => 'icon', 'iconOnly' => true, 'confirm' => '大喜利 #' . $oogiri->id . ' を削除しますか？', 'id' => 'delete-' . $oogiri->id, 'hidden' => []]) ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<?= part('partials/admin_pager', ['pager' => $pager]) ?>
<?= $this->endSection() ?>
