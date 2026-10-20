<?php
/**
 * A-02 管理ダッシュボード
 *
 * @var string $screenId
 * @var string $title
 * @var list<array{label: string, url: string, active: bool}> $periods    集計期間の切り替え
 * @var string $rangeLabel  集計対象の日付の範囲（例：「10月3日〜10月9日」）
 * @var array{conversionRate: string, answerers: string, converted: string, coefficient: string, oogiris: string, derived: string} $kpi 最重要KPI（整形済み）
 * @var list<array{label: string, value: string, sub: string}> $tiles      指標カード（整形済み）
 * @var array{bars: list<array{label: string, tip: string, x: int, y: int, height: int}>, max: string, mid: string, latest: string} $chart 日別開催数（古い順。棒の座標は計算済み）
 * @var list<array{label: string, count: string, url: string}> $todos      対応が必要なもの
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a02-dashboard.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('actions') ?>
<a class="c-abutton" href="<?= site_url('/') ?>">サイトを見る</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-dash__periods">
<nav class="c-segment c-segment--fit p-dash__period-nav" aria-label="集計期間">
<?php foreach ($periods as $period): ?>
<span class="c-segment__item"><a class="c-segment__label<?= $period['active'] ? ' is-active' : '' ?>" href="<?= esc($period['url'], 'attr') ?>"<?= $period['active'] ? ' aria-current="page"' : '' ?>><?= esc($period['label']) ?></a></span>
<?php endforeach; ?>
</nav>
<span class="p-dash__range">集計：毎日 4:00（日次バッチ）・表示は <?= esc($rangeLabel) ?></span>
</div>

<div class="p-dash__kpis">
<section class="c-panel c-panel--raised p-dash__kpi">
<h2 class="p-dash__kpi-title"><span class="c-tag">最重要KPI</span>回答者 → 開催者 転換率</h2>
<span class="p-dash__kpi-value"><?= esc($kpi['conversionRate']) ?></span>
<span class="p-dash__kpi-sub">回答者 <?= esc($kpi['answerers']) ?> → その後に開催 <?= esc($kpi['converted']) ?></span>
<span class="c-panel__note">回答を完了したブラウザのうち、その後に新規開催したブラウザの割合</span>
</section>
<section class="c-panel c-panel--raised p-dash__kpi">
<h2 class="p-dash__kpi-title"><span class="c-tag">最重要KPI</span>派生開催係数</h2>
<span class="p-dash__kpi-value"><?= esc($kpi['coefficient']) ?></span>
<span class="p-dash__kpi-sub"><?= esc($kpi['oogiris']) ?> 開催 → 参加者から <?= esc($kpi['derived']) ?> 開催</span>
<span class="c-panel__note">開催数に対して、その参加者から生まれた新規開催の比率（1.0を超えると自力で広がっている）</span>
</section>
</div>

<div class="p-dash__tiles">
<?php foreach ($tiles as $tile): ?>
<div class="c-panel p-dash__tile">
<span class="c-stat__label"><?= esc($tile['label']) ?></span>
<span class="p-dash__tile-value"><?= esc($tile['value']) ?></span>
<span class="c-stat__sub"><?= esc($tile['sub']) ?></span>
</div>
<?php endforeach; ?>
</div>

<div class="p-dash__bottom">
<section class="c-panel p-dash__chart">
<div class="c-panel__head">
<h2 class="c-panel__title">開催数（日別・直近14日）</h2>
<span class="p-dash__chart-latest"><?= esc($chart['latest']) ?></span>
</div>
<div class="p-dash__chart-area">
<div class="p-dash__chart-axis" aria-hidden="true"><span class="p-dash__chart-max"><?= esc($chart['max']) ?></span><span class="p-dash__chart-mid"><?= esc($chart['mid']) ?></span><span class="p-dash__chart-zero">0</span></div>
<svg class="p-dash__chart-svg" viewBox="0 0 140 100" preserveAspectRatio="none" role="img" aria-label="直近14日の日別開催数">
<line class="p-dash__chart-grid" x1="0" y1="50" x2="140" y2="50"></line>
<?php foreach ($chart['bars'] as $bar): ?>
<rect class="p-dash__chart-bar" x="<?= esc((string) $bar['x'], 'attr') ?>" y="<?= esc((string) $bar['y'], 'attr') ?>" width="8" height="<?= esc((string) $bar['height'], 'attr') ?>"><title><?= esc($bar['tip']) ?></title></rect>
<?php endforeach; ?>
</svg>
</div>
<div class="p-dash__chart-days" aria-hidden="true">
<?php foreach ($chart['bars'] as $bar): ?>
<span class="p-dash__chart-day"><?= esc($bar['label']) ?></span>
<?php endforeach; ?>
</div>
</section>

<section class="c-panel p-dash__todos">
<h2 class="c-panel__title">対応が必要なもの</h2>
<ul class="p-dash__todo-list">
<?php foreach ($todos as $todo): ?>
<li><a class="p-dash__todo" href="<?= esc($todo['url'], 'attr') ?>"><span><?= esc($todo['label']) ?></span><span class="p-dash__todo-count"><?= esc($todo['count']) ?></span></a></li>
<?php endforeach; ?>
</ul>
</section>
</div>
<?= $this->endSection() ?>
