<?php
/**
 * U-08 開催者専用画面（U-09 結果確認モーダルを含む）
 *
 * @var \App\Entities\Oogiri $oogiri
 * @var bool   $isOpen              募集中か（false なら結果選択待ち）
 * @var string $startsAt            募集開始日時の表示
 * @var string $endsAt              募集終了日時の表示
 * @var string $remaining           募集の残り時間（募集中のみ意味を持つ）
 * @var string $selectionRemaining  結果選出の残り時間（選択待ちのみ）
 * @var string $pv                  表示用に整形したPV
 * @var array{items: list<\App\Entities\Answer>, hasMore: bool, page: int} $answers 回答一覧（新着順、100件ずつ。結果選択待ちではお気に入りを除く）
 * @var list<\App\Entities\Answer> $favorites  お気に入り（結果選択待ちの1ページ目だけ。上にまとめて出す）
 * @var int    $favoriteCount お気に入りの件数
 * @var string $moreUrl       「もっと見る」の行き先（次のページ）
 * @var string $shareUrl
 * @var string $intentUrl
 * @var array<string, string> $errors
 * @var string $hostToken 開催者トークン（JS が localStorage に保存し直す）
 */
?>
<?= $this->extend('layouts/front') ?>

<?= $this->section('meta') ?>
<title>開催者ページ｜<?= esc($oogiri->odai) ?>｜ぼけばこ</title>
<meta name="robots" content="noindex">
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/components/sheet.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/pages/u08-host.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-host__bar">
<?= $this->include('partials/icon_lock') ?>
<span>開催者ページ</span>
</div>

<?php if (! $isOpen): ?>
<div class="p-host__banner">
<p class="p-host__banner-title">募集終了。ベスト回答を選んでください。</p>
<p class="p-host__banner-sub">選べる期限：あと <strong><?= esc($selectionRemaining) ?></strong>（過ぎると「選出なし」で公開されます）</p>
</div>
<?php endif; ?>

<section class="l-section">
<?php if (isset($errors['_'])): ?><p class="c-field__error" role="alert"><?= esc($errors['_']) ?></p><?php endif; ?>
<div class="c-field"><span class="c-field__help">お題</span><h1 class="p-host__odai"><?= esc($oogiri->odai) ?></h1></div>
<dl class="p-host__stats">
<div class="p-host__stat"><dt>回答</dt><dd><?= esc((string) $oogiri->answer_count) ?></dd></div>
<div class="p-host__stat"><dt>PV</dt><dd><?= esc($pv) ?></dd></div>
<?php if ($isOpen): ?>
<div class="p-host__stat"><dt>残り</dt><dd class="p-host__stat--small"><?= esc($remaining) ?></dd></div>
<?php else: ?>
<div class="p-host__stat"><dt>お気に入り</dt><dd><?= esc((string) $favoriteCount) ?></dd></div>
<?php endif; ?>
</dl>
<p class="u-sub">募集期間：<?= esc($startsAt) ?>〜<?= esc($endsAt) ?></p>
</section>

<?php if ($isOpen): ?>
<section class="l-section">
<a class="c-button c-button--x" href="<?= esc($intentUrl, 'attr') ?>" target="_blank" rel="noopener" data-js-track="share_recruit" data-js-track-oogiri="<?= esc($oogiri->slug, 'attr') ?>"><?= $this->include('partials/icon_share') ?>Xで再募集する</a>
<div class="p-host__url">
<label class="u-visually-hidden" for="share-url">募集URL</label>
<input class="c-input u-mono p-host__url-input" id="share-url" type="text" readonly value="<?= esc($shareUrl, 'attr') ?>">
<button type="button" class="c-button c-button--secondary p-host__copy" data-js-copy="<?= esc($shareUrl, 'attr') ?>">コピー</button>
</div>
</section>
<?php endif; ?>

<form method="post" action="<?= site_url('host/' . $oogiri->slug . '/result') ?>" data-js-once data-js-host-form>
<?= csrf_field() ?>

<?php if (! $isOpen): ?>
<section class="l-section">
<h2 class="p-host__h2">あなたのベスト3</h2>
<ol class="p-host__slots" data-js-slots>
<?php foreach ([1, 2, 3] as $rank): ?>
<li class="p-host__slot<?= $rank === 1 ? ' p-host__slot--first' : '' ?>" data-js-slot="<?= $rank ?>"><span class="p-host__rank"><?= $rank ?>位</span><span class="p-host__slot-text" data-js-slot-text>まだ選ばれていません</span></li>
<?php endforeach; ?>
</ol>
<p class="c-field__help">下の回答をタップした順に 1位・2位・3位 が入ります。もう一度タップで外せます。1件だけでも発表できます。</p>
<?php if (isset($errors['ranks'])): ?><p class="c-field__error" role="alert"><?= esc($errors['ranks']) ?></p><?php endif; ?>
</section>
<?php endif; ?>

<?php if ($favorites !== []): ?>
<section class="l-section p-host__answers">
<div class="p-host__title"><h2 class="p-host__h2">お気に入り（<?= esc((string) count($favorites)) ?>）</h2><span class="u-sub">募集中に★を付けた回答</span></div>
<ul class="p-host__list">
<?php foreach ($favorites as $answer): ?>
<?= part('partials/host_answer_item', ['answer' => $answer, 'isOpen' => $isOpen, 'slug' => $oogiri->slug]) ?>
<?php endforeach; ?>
</ul>
</section>
<?php endif; ?>

<section class="l-section p-host__answers" id="answers">
<div class="p-host__title"><h2 class="p-host__h2"><?= $favorites !== [] ? 'そのほかのボケ' : '集まったボケ' ?>（<?= esc((string) $oogiri->answer_count) ?>）</h2><span class="u-sub">新着順</span></div>
<?php if ($answers['items'] === [] && $favorites === []): ?>
<p class="p-host__empty"><?= $isOpen ? 'まだ回答はありません。Xでシェアして、ボケを集めましょう。' : 'この先の回答はありません。' ?></p>
<?php endif; ?>
<ul class="p-host__list" data-js-host-list>
<?php foreach ($answers['items'] as $answer): ?>
<?= part('partials/host_answer_item', ['answer' => $answer, 'isOpen' => $isOpen, 'slug' => $oogiri->slug]) ?>
<?php endforeach; ?>
</ul>
<?php if ($answers['hasMore']): ?>
<a class="c-button c-button--secondary u-mt-2" href="<?= esc($moreUrl, 'attr') ?>" data-js-host-more>もっと見る</a>
<?php endif; ?>
</section>

<?php if (! $isOpen): ?>
<div class="p-host__sticky">
<button type="submit" class="c-button c-button--primary" data-js-open-dialog="result-confirm" data-js-decide-btn disabled>結果を発表する<span class="p-host__pill" data-js-pick-count>0件</span></button>
</div>

<dialog class="c-sheet" id="result-confirm" aria-labelledby="result-title">
<div class="c-sheet__form">
<div class="c-sheet__handle" aria-hidden="true"></div>
<h2 class="c-sheet__title" id="result-title">この内容で発表しますか？</h2>
<p class="u-sub"><strong>お題：</strong><?= esc($oogiri->odai) ?></p>
<ol class="p-host__slots" data-js-confirm-slots>
<?php foreach ([1, 2, 3] as $rank): ?>
<li class="p-host__slot<?= $rank === 1 ? ' p-host__slot--first' : '' ?>"><span class="p-host__rank"><?= $rank ?>位</span><span class="p-host__slot-text">なし</span></li>
<?php endforeach; ?>
</ol>
<div class="c-sheet__caution"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A93115" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7.5 V13 M12 16.5 V16.6"></path></svg><p><strong>発表後は、順位を変更できません。</strong>結果ページと全回答が公開されます。</p></div>
<div class="c-sheet__actions">
<button type="submit" class="c-button c-button--primary">この内容で結果を発表する</button>
<button type="button" class="c-button c-button--ghost" data-js-close-dialog>選び直す</button>
</div>
</div>
</dialog>
<?php endif; ?>
</form>

<?php if ($isOpen): ?>
<section class="l-section">
<form method="post" action="<?= site_url('host/' . $oogiri->slug . '/delete') ?>" data-js-confirm="この大喜利を削除します。集まった回答も見られなくなります。よろしいですか？">
<?= csrf_field() ?>
<button type="submit" class="c-button c-button--ghost p-host__delete">この大喜利を削除する</button>
</form>
</section>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="application/json" data-js-config="host"><?= json_encode(['slug' => $oogiri->slug, 'token' => $hostToken], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="module" src="<?= asset_url('js/pages/host.js') ?>"></script>
<?= $this->endSection() ?>
