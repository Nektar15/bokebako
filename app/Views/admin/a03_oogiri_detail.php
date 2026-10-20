<?php
/**
 * A-03 大喜利管理（詳細）
 *
 * @var string $screenId
 * @var string $title
 * @var \App\Entities\Oogiri $oogiri
 * @var \App\Enums\OogiriStatus $status
 * @var bool   $isHidden   非表示・削除済み（「再公開」を出す）
 * @var string $createdAt  整形済み日時
 * @var string $endsAt
 * @var string $decidedAt  未確定なら「—」
 * @var string $parentId   派生元（例：「#12301」。なければ「—」）
 * @var array<string, string> $stats 見出し => 整形済みの数値（PV・回答・シェア・派生開催・通報）
 * @var array<int, \App\Entities\Answer|null> $ranked 1〜3 => ベスト回答（未選出は null）
 * @var bool   $editing    ベスト回答の代理変更フォームを開いているか（?edit=1）
 * @var list<array{answer: \App\Entities\Answer, posted: string}> $answers 回答の一覧（新しい順、先頭数件）
 * @var list<array{when: string, what: string}> $logs 操作履歴（新しい順）
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a03-oogiri.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('crumb') ?>
<a class="l-admin__crumb" href="<?= admin_url('oogiri') ?>">← 大喜利管理</a>
<?= $this->endSection() ?>

<?= $this->section('actions') ?>
<?= part('partials/admin_action_form', ['action' => admin_url('oogiri/' . $oogiri->id . '/cache'), 'label' => 'キャッシュを再生成', 'variant' => 'outline', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?>
<?php if ($isHidden): ?>
<?= part('partials/admin_action_form', ['action' => admin_url('oogiri/' . $oogiri->id . '/publish'), 'label' => '再公開する', 'variant' => 'dark', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?>
<?php else: ?>
<?= part('partials/admin_action_form', ['action' => admin_url('oogiri/' . $oogiri->id . '/hide'), 'label' => '非表示にする', 'variant' => 'dark', 'confirm' => 'この大喜利を非表示にしますか？', 'id' => 'hide-dialog', 'iconOnly' => false, 'hidden' => []]) ?>
<?php endif; ?>
<?= part('partials/admin_action_form', ['action' => admin_url('oogiri/' . $oogiri->id . '/delete'), 'label' => '削除', 'variant' => 'danger', 'confirm' => '大喜利 #' . $oogiri->id . ' を削除しますか？', 'id' => 'delete-dialog', 'iconOnly' => false, 'hidden' => []]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-oogiri-detail">
<div class="p-oogiri-detail__main">

<section class="c-panel p-oogiri-detail__summary">
<div class="p-oogiri-detail__status">
<?= part('partials/admin_badge', ['status' => $status->value, 'label' => $status->label()]) ?>
<a class="p-oogiri-detail__slug" href="<?= site_url('o/' . $oogiri->slug) ?>">/o/<?= esc($oogiri->slug) ?></a>
</div>
<p class="p-oogiri-detail__odai"><?= esc($oogiri->odai) ?></p>
<div class="p-oogiri-detail__dates">
<div class="c-stat"><span class="c-stat__label">作成</span><span class="c-stat__value c-stat__value--text"><?= esc($createdAt) ?></span></div>
<div class="c-stat"><span class="c-stat__label">募集終了</span><span class="c-stat__value c-stat__value--text"><?= esc($endsAt) ?></span></div>
<div class="c-stat"><span class="c-stat__label">結果確定</span><span class="c-stat__value c-stat__value--text"><?= esc($decidedAt) ?></span></div>
<div class="c-stat"><span class="c-stat__label">派生元</span><span class="c-stat__value c-stat__value--text"><?= esc($parentId) ?></span></div>
</div>
<div class="c-panel__divider"></div>
<div class="p-oogiri-detail__stats">
<?php foreach ($stats as $label => $value): ?>
<div class="c-stat"><span class="c-stat__label"><?= esc($label) ?></span><span class="c-stat__value"><?= esc($value) ?></span></div>
<?php endforeach; ?>
</div>
</section>

<section class="c-panel">
<div class="c-panel__head">
<h2 class="c-panel__title">ベスト回答</h2>
<?php if ($editing): ?>
<span class="c-abutton c-abutton--small" aria-current="true">編集中</span>
<?php else: ?>
<a class="c-abutton c-abutton--small" href="<?= admin_url('oogiri/' . $oogiri->id . '?edit=1') ?>">順位を代理で変更</a>
<?php endif; ?>
</div>
<?php if ($editing): ?>
<form method="post" action="<?= admin_url('oogiri/' . $oogiri->id . '/result') ?>" class="p-oogiri-detail__edit" data-js-once>
<?= csrf_field() ?>
<p class="c-callout">開催者の誤操作に対応するときだけ使います。保存すると、結果ページとOGP画像を作り直し、操作履歴に記録します。</p>
<ol class="p-oogiri-detail__ranks u-mt-2">
<?php foreach ($ranked as $rank => $current): ?>
<li class="p-oogiri-detail__rank">
<span class="p-oogiri-detail__rank-no<?= $rank === 1 ? ' p-oogiri-detail__rank-no--first' : '' ?>"><?= esc((string) $rank) ?>位</span>
<select class="c-aselect p-oogiri-detail__rank-select" name="ranks[]" aria-label="<?= esc((string) $rank, 'attr') ?>位の回答">
<option value="0"<?= $current === null ? ' selected' : '' ?>>（なし）</option>
<?php foreach ($answers as $row): ?>
<option value="<?= esc((string) $row['answer']->id, 'attr') ?>"<?= $current !== null && $current->id === $row['answer']->id ? ' selected' : '' ?>><?= esc($row['answer']->body) ?></option>
<?php endforeach; ?>
</select>
</li>
<?php endforeach; ?>
</ol>
<div class="c-panel__actions u-mt-2">
<a class="c-abutton c-abutton--link" href="<?= admin_url('oogiri/' . $oogiri->id) ?>">取り消す</a>
<button type="submit" class="c-abutton c-abutton--primary">順位を保存する</button>
</div>
</form>
<?php else: ?>
<ol class="p-oogiri-detail__ranks">
<?php foreach ($ranked as $rank => $current): ?>
<li class="p-oogiri-detail__rank">
<span class="p-oogiri-detail__rank-no<?= $rank === 1 ? ' p-oogiri-detail__rank-no--first' : '' ?>"><?= esc((string) $rank) ?>位</span>
<?php if ($current === null): ?>
<span class="p-oogiri-detail__rank-text p-oogiri-detail__rank-text--none">なし</span>
<?php else: ?>
<span class="p-oogiri-detail__rank-text"><?= esc($current->body) ?></span>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ol>
<?php endif; ?>
</section>

<section class="c-panel c-panel--table">
<div class="c-panel__head c-panel__head--table">
<h2 class="c-panel__title">回答（<?= esc((string) $oogiri->answer_count) ?>）</h2>
<a class="c-panel__link" href="<?= admin_url('answers?oogiri=' . $oogiri->id) ?>">回答管理で開く</a>
</div>
<div class="c-table p-oogiri-detail__answers" role="table">
<div class="c-table__row c-table__row--head" role="row">
<div class="c-table__head" role="columnheader">ID</div>
<div class="c-table__head" role="columnheader">本文</div>
<div class="c-table__head" role="columnheader">投稿日時</div>
<div class="c-table__head c-table__head--num" role="columnheader">通報</div>
<div class="c-table__head" role="columnheader">状態</div>
<div class="c-table__head" role="columnheader"></div>
</div>
<?php if ($answers === []): ?>
<p class="c-table__empty">まだ回答はありません。</p>
<?php endif; ?>
<?php foreach ($answers as $row): ?>
<?php $answer = $row['answer']; ?>
<div class="c-table__row" role="row">
<div class="c-table__cell c-table__cell--id" role="cell">A-<?= esc((string) $answer->id) ?></div>
<div class="c-table__cell c-table__cell--strong" role="cell"><?= nl2br_esc($answer->body) ?></div>
<div class="c-table__cell c-table__cell--time" role="cell"><?= esc($row['posted']) ?></div>
<div class="c-table__cell c-table__cell--num" role="cell"><?= esc((string) $answer->report_count) ?></div>
<div class="c-table__cell" role="cell"><?= part('partials/admin_badge', ['status' => $answer->status()->value, 'label' => $answer->status()->label()]) ?></div>
<div class="c-table__cell" role="cell"><?= part('partials/admin_action_form', ['action' => admin_url('answers/' . $answer->id . '/hide'), 'label' => '非表示', 'variant' => 'tiny', 'iconOnly' => false, 'confirm' => null, 'id' => '', 'hidden' => []]) ?></div>
</div>
<?php endforeach; ?>
</div>
</section>

</div>
<div class="p-oogiri-detail__side">

<section class="c-panel c-panel--tight">
<h2 class="c-panel__title">通報履歴</h2>
<?php if ($oogiri->report_count === 0): ?>
<p class="c-panel__empty">この大喜利への通報はありません。</p>
<?php else: ?>
<p class="c-panel__empty"><?= esc($stats['通報']) ?> 件の通報があります。<a href="<?= admin_url('moderation') ?>">通報・モデレーションで確認</a></p>
<?php endif; ?>
</section>

<section class="c-panel c-panel--tight">
<h2 class="c-panel__title">派生</h2>
<p class="p-oogiri-detail__derived">この大喜利の参加者から <b class="p-oogiri-detail__derived-count"><?= esc($stats['派生開催']) ?></b> 件の開催が生まれました。</p>
<p class="c-panel__note">回答完了画面・結果ページの「自分も大喜利を開催する」から（内訳は工程4で集計）</p>
</section>

<section class="c-panel c-panel--tight">
<h2 class="c-panel__title">操作履歴</h2>
<?= part('partials/admin_history', ['logs' => $logs, 'empty' => 'まだ操作はありません']) ?>
</section>

</div>
</div>
<?= $this->endSection() ?>
