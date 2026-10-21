<?php

use App\Entities\Inquiry;
use App\Enums\InquiryStatus;

/**
 * A-09 お問い合わせ管理
 *
 * @var string                                                                        $screenId
 * @var string                                                                        $title
 * @var array{status: string, type: string, period: string, from: string, to: string} $filter 絞り込みの現在値
 * @var array<string, string>                                                         $types   種別の選択肢（値 => 表示名）
 * @var array<string, string>                                                         $periods 期間の選択肢
 * @var list<array{label: string, count: string|null, url: string, active: bool}>     $tabs 対応状況のタブ
 * @var list<array{inquiry: Inquiry, time: string, url: string, active: bool}>        $items 左の一覧（新しい順）
 * @var Inquiry|null                                                                  $selected   右側に出す1件。一覧が空なら null
 * @var string                                                                        $selectedAt 受付日時（整形済み）
 * @var list<array{when: string, what: string}>                                       $memos 対応メモ（新しい順）
 * @var list<InquiryStatus>                                                           $statusCases
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a09-inquiries.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-inq__filters">
<?= part('partials/admin_tabs', ['tabs' => $tabs, 'ariaLabel' => '対応状況']) ?>
<form class="p-inq__selects" method="get" action="<?= admin_url('inquiries') ?>">
<input type="hidden" name="status" value="<?= esc($filter['status'], 'attr') ?>">
<select class="c-aselect p-inq__type" name="type" aria-label="種別" data-js-autosubmit>
<option value="all"<?= $filter['type'] === 'all' ? ' selected' : '' ?>>種別：すべて</option>
<?php foreach ($types as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"<?= $value === $filter['type'] ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<select class="c-aselect p-inq__period" name="period" aria-label="期間" data-js-autosubmit>
<?php foreach ($periods as $value => $label): ?>
<option value="<?= esc($value, 'attr') ?>"<?= $value === $filter['period'] ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach; ?>
</select>
<?= part('partials/admin_date_range', ['from' => $filter['from'], 'to' => $filter['to']]) ?>
<button type="submit" class="c-abutton c-abutton--small" data-js-hide-with-js>絞り込む</button>
</form>
</div>

<div class="p-inq">
<ul class="p-inq__list">
<?php if ($items === []): ?>
<li class="c-panel__empty">この条件のお問い合わせはありません。</li>
<?php endif; ?>
<?php foreach ($items as $row): ?>
<?php $inquiry = $row['inquiry']; ?>
<li>
<a class="p-inq__card<?= $row['active'] ? ' is-active' : '' ?>" href="<?= esc($row['url'] . '#detail', 'attr') ?>"<?= $row['active'] ? ' aria-current="true"' : '' ?>>
<span class="p-inq__card-head">
<?= part('partials/admin_badge', ['status' => $inquiry->status()->value, 'label' => $inquiry->status()->label()]) ?>
<span class="p-inq__card-type"><?= esc($inquiry->type()->label()) ?></span>
<span class="p-inq__card-time"><?= esc($row['time']) ?></span>
</span>
<span class="p-inq__card-body"><?= esc($inquiry->body) ?></span>
<span class="p-inq__card-mail"><?= esc($inquiry->email) ?></span>
</a>
</li>
<?php endforeach; ?>
</ul>

<?php if ($selected !== null): ?>
<section id="detail" class="c-panel c-panel--raised p-inq__detail" aria-label="選択中のお問い合わせ">
<dl class="p-inq__meta">
<dt>受付日時</dt><dd><?= esc($selectedAt) ?></dd>
<dt>種別</dt><dd class="p-inq__meta-strong"><?= esc($selected->type()->label()) ?></dd>
<dt>メール</dt><dd class="p-inq__meta-mail"><span class="u-mono"><?= esc($selected->email) ?></span><a class="c-panel__link" href="mailto:<?= esc($selected->email, 'attr') ?>">メールで返信</a></dd>
<dt>対象のURL</dt><dd class="u-mono p-inq__meta-url"><?php if ($selected->url === ''): ?>—<?php else: ?><a href="<?= esc($selected->url, 'attr') ?>"><?= esc($selected->url) ?></a><?php endif; ?></dd>
</dl>
<div class="p-inq__body"><?= nl2br_esc($selected->body) ?></div>

<form class="p-inq__form" method="post" action="<?= admin_url('inquiries/' . $selected->id) ?>" data-js-once>
<?= csrf_field() ?>
<div class="c-afield">
<span class="c-afield__label" id="inq-status-label">ステータス</span>
<div class="c-segment c-segment--fit" role="group" aria-labelledby="inq-status-label">
<?php foreach ($statusCases as $status): ?>
<span class="c-segment__item"><input class="c-segment__input" type="radio" name="status" id="inq-status-<?= esc($status->value, 'attr') ?>" value="<?= esc($status->value, 'attr') ?>"<?= $status === $selected->status() ? ' checked' : '' ?>><label class="c-segment__label" for="inq-status-<?= esc($status->value, 'attr') ?>"><?= esc($status->label()) ?></label></span>
<?php endforeach; ?>
</div>
</div>
<div class="c-afield">
<label class="c-afield__label" for="inq-memo">対応メモ</label>
<textarea class="c-atextarea c-atextarea--full" id="inq-memo" name="memo" rows="3" placeholder="対応した内容を記録します（社内のみ）"></textarea>
<div class="c-panel__actions"><button type="submit" class="c-abutton c-abutton--dark">保存する</button></div>
</div>
</form>
<div class="c-history--inline"><?= part('partials/admin_history', ['logs' => $memos, 'empty' => 'まだメモはありません']) ?></div>
</section>
<?php endif; ?>
</div>
<?= $this->endSection() ?>
