<?php

use App\Entities\Ad;
use App\Enums\AdSlot;

/**
 * A-07 広告管理
 *
 * @var string                                                                                              $screenId
 * @var string                                                                                              $title
 * @var list<array{label: string, live: string, impressions: string, clicks: string, ctr: string}>          $slots 枠ごとの今月の実績（整形済み）
 * @var list<array{ad: Ad, period: string, impressions: string, clicks: string, ctr: string}>               $items 一覧の行
 * @var Ad|null                                                                                             $editing   編集中の広告（登録なら null）
 * @var list<AdSlot>                                                                                        $slotCases
 * @var array{name: string, sponsor: string, url: string, slot: string, starts_at: string, ends_at: string} $form フォームの初期値
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a07-ads.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-ads__slots">
<?php foreach ($slots as $slot): ?>
<section class="c-panel c-panel--tight p-ads__slot">
<div class="c-stat"><span class="p-ads__slot-name"><?= esc($slot['label']) ?></span><span class="c-stat__sub"><?= esc($slot['live']) ?></span></div>
<div class="c-stat"><span class="c-stat__label">今月の表示</span><span class="c-stat__value"><?= esc($slot['impressions']) ?></span></div>
<div class="c-stat"><span class="c-stat__label">クリック</span><span class="c-stat__value"><?= esc($slot['clicks']) ?></span></div>
<div class="c-stat"><span class="c-stat__label">CTR</span><span class="c-stat__value"><?= esc($slot['ctr']) ?></span></div>
</section>
<?php endforeach; ?>
</div>

<div class="p-ads">
<div class="c-panel c-panel--table">
<div class="c-table p-ads__table" role="table">
<div class="c-table__row c-table__row--head" role="row">
<div class="c-table__head" role="columnheader">広告名 ／ スポンサー</div>
<div class="c-table__head" role="columnheader">枠</div>
<div class="c-table__head" role="columnheader">掲載期間</div>
<div class="c-table__head" role="columnheader">状態</div>
<div class="c-table__head c-table__head--num" role="columnheader">表示</div>
<div class="c-table__head c-table__head--num" role="columnheader">クリック</div>
<div class="c-table__head c-table__head--num" role="columnheader">CTR</div>
<div class="c-table__head" role="columnheader"></div>
</div>
<?php if ($items === []): ?>
<p class="c-table__empty">広告はまだ登録されていません。</p>
<?php endif; ?>
<?php foreach ($items as $row): ?>
<?php $ad = $row['ad']; ?>
<div class="c-table__row" role="row">
<div class="c-table__cell" role="cell" data-label="広告名 ／ スポンサー"><div class="c-table__stack"><span class="p-ads__name"><?= esc($ad->name) ?></span><span class="c-table__cell--sub"><?= esc($ad->sponsor) ?></span></div></div>
<div class="c-table__cell c-table__cell--sub" role="cell" data-label="枠"><?= esc($ad->slot()->label()) ?></div>
<div class="c-table__cell c-table__cell--time" role="cell" data-label="掲載期間"><?= esc($row['period']) ?></div>
<div class="c-table__cell" role="cell" data-label="状態"><?= part('partials/admin_badge', ['status' => $ad->status()->value, 'label' => $ad->status()->label()]) ?></div>
<div class="c-table__cell c-table__cell--num" role="cell" data-label="表示"><?= esc($row['impressions']) ?></div>
<div class="c-table__cell c-table__cell--num" role="cell" data-label="クリック"><?= esc($row['clicks']) ?></div>
<div class="c-table__cell c-table__cell--num" role="cell" data-label="CTR"><?= esc($row['ctr']) ?></div>
<div class="c-table__cell" role="cell"><a class="c-abutton c-abutton--tiny" href="<?= admin_url('ads?edit=' . $ad->id) ?>">編集</a></div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="p-ads__side">
<form class="c-panel c-panel--raised p-ads__form" method="post" action="<?= $editing !== null ? admin_url('ads/' . $editing->id) : admin_url('ads') ?>" enctype="multipart/form-data" data-js-once>
<?= csrf_field() ?>
<h2 class="c-panel__title c-panel__title--lg"><?= $editing !== null ? '広告を編集' : '広告を登録' ?></h2>
<div class="c-afield">
<label class="c-afield__label" for="ad-name">広告名</label>
<input class="c-ainput c-ainput--full" id="ad-name" name="name" type="text" placeholder="例：秋の新商品キャンペーン" required value="<?= esc($form['name'], 'attr') ?>">
</div>
<div class="c-afield">
<label class="c-afield__label" for="ad-sponsor">スポンサー名</label>
<input class="c-ainput c-ainput--full" id="ad-sponsor" name="sponsor" type="text" placeholder="例：まるや製菓" required value="<?= esc($form['sponsor'], 'attr') ?>">
</div>
<div class="c-afield">
<span class="c-afield__label">広告画像</span>
<label class="c-upload"><input class="c-upload__input" type="file" name="image" accept="image/png,image/jpeg"<?= $editing === null ? ' required' : '' ?>><span data-js-upload-label>画像を選ぶ（またはドロップ）</span></label>
<p class="c-afield__help">PNG・JPG。表示サイズは320×50（640×100の2倍サイズを推奨）。<?= $editing !== null ? '選ばなければ今の画像のままです。' : '' ?></p>
</div>
<div class="c-afield">
<label class="c-afield__label" for="ad-url">リンク先URL</label>
<input class="c-ainput c-ainput--full" id="ad-url" name="url" type="url" placeholder="https://" required value="<?= esc($form['url'], 'attr') ?>">
<p class="c-afield__help">クリックは計測用URLを経由して、このURLへ移動します。</p>
</div>
<fieldset class="c-afield">
<legend class="c-afield__label">掲載場所</legend>
<div class="c-checks">
<?php foreach ($slotCases as $slot): ?>
<label class="c-checks__item"><input type="radio" name="slot" value="<?= esc($slot->value, 'attr') ?>"<?= $slot->value === $form['slot'] ? ' checked' : '' ?>><?= esc($slot->label()) ?></label>
<?php endforeach; ?>
</div>
</fieldset>
<div class="c-afield__row">
<div class="c-afield"><label class="c-afield__label" for="ad-starts">掲載開始</label><input class="c-ainput c-ainput--full" id="ad-starts" name="starts_at" type="datetime-local" required value="<?= esc($form['starts_at'], 'attr') ?>"></div>
<div class="c-afield"><label class="c-afield__label" for="ad-ends">掲載終了</label><input class="c-ainput c-ainput--full" id="ad-ends" name="ends_at" type="datetime-local" required value="<?= esc($form['ends_at'], 'attr') ?>"></div>
</div>
<div class="c-panel__actions<?= $editing !== null ? ' c-panel__actions--between' : '' ?>">
<?php if ($editing !== null): ?>
<a class="c-abutton c-abutton--link" href="<?= admin_url('ads') ?>">登録に戻る</a>
<?php endif; ?>
<button type="submit" class="c-abutton" name="action" value="draft"><?= $editing !== null ? '停止して保存' : '下書き保存' ?></button>
<button type="submit" class="c-abutton c-abutton--primary" name="action" value="publish"><?= $editing !== null ? '公開して保存' : '公開する' ?></button>
</div>
</form>
<?php if ($editing !== null): ?>
<div class="p-ads__delete">
<?= part('partials/admin_action_form', ['action' => admin_url('ads/' . $editing->id . '/delete'), 'label' => 'この広告を削除', 'variant' => 'danger', 'confirm' => '「' . $editing->name . '」を削除しますか？', 'id' => 'delete-dialog', 'iconOnly' => false, 'hidden' => []]) ?>
</div>
<?php endif; ?>
</div>
</div>
<?= $this->endSection() ?>
