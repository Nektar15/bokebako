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
 * @var list<\App\Entities\Answer> $answers 回答一覧（新着順）
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
<div class="p-host__stat"><dt>お気に入り</dt><dd><?= count(array_filter($answers, static fn ($a) => $a->is_favorite)) ?></dd></div>
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

<section class="l-section p-host__answers">
<div class="p-host__title"><h2 class="p-host__h2">集まったボケ（<?= esc((string) $oogiri->answer_count) ?>）</h2><span class="u-sub">新着順</span></div>
<?php if ($answers === []): ?>
<p class="p-host__empty">まだ回答はありません。Xでシェアして、ボケを集めましょう。</p>
<?php endif; ?>
<ul class="p-host__list">
<?php foreach ($answers as $answer): ?>
<li class="p-host__item" data-js-answer="<?= $answer->id ?>">
<?php if ($isOpen): ?>
<p class="p-host__body"><?= nl2br_esc($answer->body) ?></p>
<div class="p-host__item-meta">
<span class="u-sub"><?= esc($answer->posted_label ?? '') ?></span>
<button type="submit" form="favorite-<?= $answer->id ?>" class="p-host__fav<?= $answer->is_favorite ? ' is-on' : '' ?>" aria-pressed="<?= $answer->is_favorite ? 'true' : 'false' ?>" aria-label="お気に入り">★</button>
</div>
<?php else: ?>
<label class="p-host__pick">
<input type="checkbox" class="u-visually-hidden" name="ranks[]" value="<?= $answer->id ?>" data-js-pick<?= $answer->rank !== null ? ' checked' : '' ?>>
<span class="p-host__pick-badge" data-js-pick-badge></span>
<span class="p-host__pick-body"><span class="p-host__body"><?= nl2br_esc($answer->body) ?></span><span class="u-sub"><?= esc($answer->posted_label ?? '') ?><?= $answer->is_favorite ? ' ・ ★お気に入り' : '' ?></span></span>
</label>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
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
<?php foreach ($answers as $answer): ?>
<form class="u-hidden" id="favorite-<?= $answer->id ?>" method="post" action="<?= site_url('host/' . $oogiri->slug . '/favorites/' . $answer->id) ?>"><?= csrf_field() ?></form>
<?php endforeach; ?>
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
