<?php
/**
 * 回答一覧（U-07 / U-10 / U-12）
 *
 * @var \App\Entities\Oogiri       $oogiri
 * @var array{items: list<\App\Entities\Answer>, hasMore: bool, page: int} $answers
 * @var callable(\App\Entities\Answer): string $answerIntentUrl  回答シェア用の Web Intent URL
 * @var string $variant  card（U-07：カード）| row（U-10・U-12：罫線）。省略時 row
 * @var string $moreUrl  「もっと見る」のリンク先（ページング）。省略時は現在のURLに page を足す
 */
$variant ??= 'row';
$moreUrl ??= current_url() . '?page=' . ($answers['page'] + 1);
?>
<ul class="c-answers c-answers--<?= esc($variant, 'attr') ?>">
<?php foreach ($answers['items'] as $answer): ?>
<li class="c-answers__item<?= $answer->is_mine ? ' is-mine' : '' ?>">
<?php if ($answer->is_mine): ?><span class="c-answers__mine">あなたの回答</span><?php endif; ?>
<p class="c-answers__body"><?= nl2br_esc($answer->body) ?></p>
<div class="c-answers__meta">
<span class="c-answers__time"><?= esc($answer->posted_label ?? '') ?></span>
<div class="c-answers__actions">
<a class="c-iconbtn" href="<?= esc($answerIntentUrl($answer), 'attr') ?>" target="_blank" rel="noopener" aria-label="この回答をXでシェア" data-js-track="share_answer" data-js-track-oogiri="<?= esc($oogiri->slug, 'attr') ?>" data-js-track-answer="<?= $answer->id ?>"><?= $this->include('partials/icon_share') ?></a>
<button type="button" class="c-iconbtn c-iconbtn--muted" aria-label="この回答を通報" data-js-open-dialog="report-dialog" data-js-report-type="answer" data-js-report-id="<?= $answer->id ?>"><?= $this->include('partials/icon_flag') ?></button>
</div>
</div>
</li>
<?php endforeach; ?>
</ul>
<?php if ($answers['hasMore']): ?>
<a class="c-button c-button--secondary u-mt-2" href="<?= esc($moreUrl, 'attr') ?>" data-js-load-more>もっと見る</a>
<?php endif; ?>
