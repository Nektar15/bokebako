<?php
/**
 * A-05 通報・モデレーション
 *
 * @var string $screenId
 * @var string $title
 * @var list<array{label: string, count: string|null, url: string, active: bool}> $tabs 絞り込み
 * @var list<array{item: \App\Entities\ModerationItem, url: string, active: bool, time: string, flags: list<array{value: string, label: string}>, stateLabel: string}> $queue 左の一覧（優先度順）
 * @var \App\Entities\ModerationItem|null $selected   右側に出す1件。一覧が空なら null
 * @var string                            $selectedAt 対象の投稿日時（整形済み）
 * @var list<array{when: string, what: string}> $history 対応履歴（新しい順）
 * @var array<string, string> $restrictHours 投稿元を制限する期間の選択肢（値 => 表示名）
 * @var bool   $hasOverflow 古すぎて一覧に読み込めなかった未処理があるか
 * @var string $overflow    その件数（整形済み）
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a05-moderation.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-mod__filters">
<?= part('partials/admin_tabs', ['tabs' => $tabs, 'ariaLabel' => '絞り込み']) ?>
<span class="p-mod__order">優先度の高い順（通報急増 → 通報件数 → NG検知 → 異常投稿 → AI判定）</span>
</div>
<?php if ($hasOverflow): ?>
<p class="c-notice c-notice--error" role="status">未処理が多いため、古い <?= esc($overflow) ?> 件はこの一覧に出ていません。上から処理すると順に表示されます。</p>
<?php endif; ?>

<div class="p-mod">
<ul class="p-mod__queue">
<?php if ($queue === []): ?>
<li class="c-panel__empty">この条件の投稿はありません。</li>
<?php endif; ?>
<?php foreach ($queue as $row): ?>
<?php $item = $row['item']; ?>
<li>
<a class="p-mod__card<?= $row['active'] ? ' is-active' : '' ?>" href="<?= esc($row['url'], 'attr') ?>"<?= $row['active'] ? ' aria-current="true"' : '' ?>>
<span class="p-mod__card-head">
<?php foreach ($row['flags'] as $flag): ?>
<span class="c-status c-status--small p-mod__flag p-mod__flag--<?= esc($flag['value'], 'attr') ?>"><?= esc($flag['label']) ?></span>
<?php endforeach; ?>
<span class="p-mod__card-time"><?= esc($row['time']) ?></span>
</span>
<span class="p-mod__card-text"><?= esc($item->text) ?></span>
<span class="p-mod__card-meta"><?= esc($item->target()->label()) ?> ・ 通報 <?= esc((string) $item->report_count) ?>件 ・ <?= esc($row['stateLabel']) ?></span>
</a>
</li>
<?php endforeach; ?>
</ul>

<?php if ($selected !== null): ?>
<section class="c-panel c-panel--raised p-mod__detail" aria-label="選択中の投稿">
<div class="p-mod__detail-head">
<span class="c-tag c-tag--ink c-tag--small p-mod__kind"><?= esc($selected->target()->label()) ?></span>
<span class="u-mono p-mod__id"><?= esc($selected->displayId()) ?></span>
<?= part('partials/admin_badge', ['status' => $selected->target_status, 'label' => $selected->targetStatusLabel()]) ?>
<a class="c-panel__link p-mod__open" href="<?= admin_url('oogiri/' . $selected->oogiri_id) ?>">大喜利を開く</a>
</div>
<div class="p-mod__target">
<?php if ($selected->odai !== ''): ?><span class="p-mod__target-odai">お題：<?= esc($selected->odai) ?></span><?php endif; ?>
<p class="p-mod__target-text"><?= nl2br_esc($selected->text) ?></p>
</div>
<div class="p-mod__facts">
<div class="c-stat"><span class="c-stat__label">通報</span><span class="c-stat__value c-stat__value--text"><?= esc($selected->report_detail) ?></span></div>
<div class="c-stat"><span class="c-stat__label">NGワード検知</span><span class="c-stat__value c-stat__value--text"><?= esc($selected->ng_detail) ?></span></div>
<div class="c-stat"><span class="c-stat__label">異常投稿判定</span><span class="c-stat__value c-stat__value--text"><?= esc($selected->abnormal) ?></span></div>
<div class="c-stat"><span class="c-stat__label">投稿日時</span><span class="c-stat__value c-stat__value--text"><?= esc($selectedAt) ?></span></div>
</div>
<div class="p-mod__ai">
<div class="p-mod__ai-head">
<span class="p-mod__ai-title">AI補助判定</span>
<?= part('partials/admin_badge', ['status' => $selected->aiVerdict()->value, 'label' => $selected->aiVerdict()->label()]) ?>
<span class="p-mod__ai-note">GPT-5 nano ・ 参考情報です。最終判断は管理者が行います</span>
</div>
<p class="p-mod__ai-reason"><?= esc($selected->ai_reason) ?></p>
</div>
<div class="p-mod__actions">
<?= part('partials/admin_action_form', ['action' => admin_url('moderation/' . $selected->key() . '/resolve'), 'label' => '問題なし（通報を処理済みに）', 'variant' => 'outline', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?>
<?= part('partials/admin_action_form', ['action' => admin_url('moderation/' . $selected->key() . '/hide'), 'label' => '非表示にする', 'variant' => 'dark', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?>
<?= part('partials/admin_action_form', ['action' => admin_url('moderation/' . $selected->key() . '/delete'), 'label' => '削除', 'variant' => 'danger', 'confirm' => 'この投稿を削除しますか？', 'id' => 'delete-dialog', 'iconOnly' => false, 'hidden' => []]) ?>
<form class="p-mod__restrict" method="post" action="<?= admin_url('moderation/' . $selected->key() . '/restrict') ?>" data-js-once>
<?= csrf_field() ?>
<select class="c-aselect p-mod__restrict-hours" name="hours" aria-label="制限する期間">
<?php foreach ($restrictHours as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<button type="submit" class="c-abutton">投稿元を制限</button>
</form>
</div>
<div>
<h3 class="p-mod__history-title">対応履歴</h3>
<div class="c-history--inline"><?= part('partials/admin_history', ['logs' => $history, 'empty' => 'まだ対応していません']) ?></div>
</div>
</section>
<?php endif; ?>
</div>
<?= $this->endSection() ?>
