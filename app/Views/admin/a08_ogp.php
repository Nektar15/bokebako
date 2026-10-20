<?php
/**
 * A-08 OGP管理
 *
 * @var string $screenId
 * @var string $title
 * @var list<\App\Enums\OgpKind> $kinds  3種類のテンプレート
 * @var array<string, string>    $assets 差し替えられる画像（値 => 表示名：背景・ロゴ）
 * @var array{kind: string, target: string} $form 「個別に再生成する」の初期値
 * @var list<array{job: \App\Entities\OgpJob, time: string, status: string, label: string}> $jobs 最近の生成（新しい順。status は ok / failed）
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a08-ogp.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-ogp__templates">
<?php foreach ($kinds as $kind): ?>
<section class="c-panel c-panel--tight p-ogp__template">
<div class="c-panel__head"><h2 class="c-panel__title"><?= esc($kind->label()) ?></h2><span class="c-panel__sub"><?= esc($kind->timing()) ?></span></div>
<?php if ($kind === \App\Enums\OgpKind::Recruit): ?>
<div class="p-ogp__preview p-ogp__preview--recruit" role="img" aria-label="募集用OGPの見本">
<div class="p-ogp__preview-head"><span class="p-ogp__brand"><?= $this->include('partials/logo') ?>ぼけばこ</span><span class="p-ogp__pill">24時間限定 回答募集中</span></div>
<span class="p-ogp__preview-odai">こんなコンビニ店員は嫌だ</span>
</div>
<?php elseif ($kind === \App\Enums\OgpKind::Answer): ?>
<div class="p-ogp__preview" role="img" aria-label="回答シェア用OGPの見本">
<span class="p-ogp__preview-label">お題：こんなコンビニ店員は嫌だ</span>
<span class="p-ogp__preview-answer">「袋いりますか？」のあとに、10秒の沈黙を置いてくる</span>
<span class="p-ogp__brand p-ogp__brand--end"><?= $this->include('partials/logo') ?>ぼけばこ</span>
</div>
<?php else: ?>
<div class="p-ogp__preview" role="img" aria-label="結果用OGPの見本">
<div class="p-ogp__preview-head"><span class="p-ogp__brand"><?= $this->include('partials/logo') ?>ぼけばこ</span><span class="p-ogp__stamp">結果<br>発表</span></div>
<span class="p-ogp__preview-label">お題：絶対に売れない駄菓子の商品名とは？</span>
<span class="p-ogp__preview-first"><span class="p-ogp__rank">1位</span>うまい棒・味なし</span>
<span class="p-ogp__preview-count">全412回答</span>
</div>
<?php endif; ?>
<p class="c-panel__note">入る要素：<?= esc($kind->elements()) ?></p>
<div class="p-ogp__assets">
<?php foreach ($assets as $asset => $assetLabel): ?>
<form class="c-action" method="post" action="<?= admin_url('ogp/template') ?>" enctype="multipart/form-data" data-js-once>
<?= csrf_field() ?>
<input type="hidden" name="kind" value="<?= esc($kind->value, 'attr') ?>">
<input type="hidden" name="asset" value="<?= esc($asset, 'attr') ?>">
<label class="c-abutton c-abutton--small p-ogp__asset-button"><?= esc($assetLabel) ?>を差し替え<input class="c-upload__input" type="file" name="file" accept="image/png,image/jpeg" required data-js-autosubmit></label>
<button type="submit" class="c-abutton c-abutton--small" data-js-hide-with-js>送る</button>
</form>
<?php endforeach; ?>
</div>
</section>
<?php endforeach; ?>
</div>
<p class="c-panel__note">画像は1200×630px。文字数に合わせて自動で折り返し、文字の大きさを調整します。フォントはサーバーに置いたものを使います。</p>

<div class="p-ogp">
<form class="c-panel c-panel--raised p-ogp__regen" method="post" action="<?= admin_url('ogp/regenerate') ?>" data-js-once data-js-ogp-form>
<?= csrf_field() ?>
<h2 class="c-panel__title c-panel__title--lg">個別に再生成する</h2>
<div class="c-segment" role="group" aria-label="種類">
<?php foreach ($kinds as $kind): ?>
<span class="c-segment__item"><input class="c-segment__input" type="radio" name="kind" id="ogp-kind-<?= esc($kind->value, 'attr') ?>" value="<?= esc($kind->value, 'attr') ?>"<?= $kind->value === $form['kind'] ? ' checked' : '' ?>><label class="c-segment__label" for="ogp-kind-<?= esc($kind->value, 'attr') ?>"><?= esc($kind->label()) ?></label></span>
<?php endforeach; ?>
</div>
<div class="c-afield">
<label class="c-afield__label" for="ogp-target" data-js-ogp-target-label data-js-label-oogiri="大喜利のURL・スラッグ・ID" data-js-label-answer="回答ID">大喜利のURL・スラッグ・ID ／ 回答ID</label>
<input class="c-ainput c-ainput--full" id="ogp-target" name="target" type="text" placeholder="例：https://bokebako.com/o/k7Qm2xPz" required value="<?= esc($form['target'], 'attr') ?>" data-js-ogp-target data-js-ph-oogiri="例：https://bokebako.com/o/k7Qm2xPz" data-js-ph-answer="例：A-90412">
</div>
<button type="submit" class="c-abutton c-abutton--primary c-abutton--tall">再生成する</button>
<p class="c-panel__note">古い画像はXなどにキャッシュされているため、反映まで時間がかかることがあります。</p>
</form>

<section class="c-panel c-panel--table">
<h2 class="c-panel__title p-ogp__jobs-title">最近の生成</h2>
<div class="c-table p-ogp__jobs" role="table">
<div class="c-table__row c-table__row--head" role="row">
<div class="c-table__head" role="columnheader">日時</div>
<div class="c-table__head" role="columnheader">種類</div>
<div class="c-table__head" role="columnheader">対象</div>
<div class="c-table__head" role="columnheader">実行</div>
<div class="c-table__head" role="columnheader">状態</div>
<div class="c-table__head" role="columnheader"></div>
</div>
<?php foreach ($jobs as $row): ?>
<?php $job = $row['job']; ?>
<div class="c-table__row" role="row">
<div class="c-table__cell c-table__cell--time" role="cell"><?= esc($row['time']) ?></div>
<div class="c-table__cell c-table__cell--sub" role="cell"><?= esc($job->kind()->label()) ?></div>
<div class="c-table__cell c-table__cell--sub c-table__cell--clip" role="cell"><?= esc($job->target) ?></div>
<div class="c-table__cell c-table__cell--sub" role="cell"><?= esc($job->actor) ?></div>
<div class="c-table__cell" role="cell"><?= part('partials/admin_badge', ['status' => $row['status'], 'label' => $row['label']]) ?></div>
<div class="c-table__cell" role="cell">
<?php if (! $job->succeeded): ?>
<?= part('partials/admin_action_form', ['action' => admin_url('ogp/regenerate'), 'label' => '再試行', 'variant' => 'tiny', 'hidden' => ['kind' => $job->kind()->value, 'target' => (string) $job->target_id], 'iconOnly' => false, 'confirm' => null, 'id' => '']) ?>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
</section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= asset_url('js/pages/ogp.js') ?>"></script>
<?= $this->endSection() ?>
