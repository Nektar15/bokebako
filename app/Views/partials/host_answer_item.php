<?php

use App\Entities\Answer;

/**
 * U-08 開催者画面の回答1件（募集中：いいねボタン、結果選択待ち：ベスト回答の選択）
 *
 * いいね（開催者だけが押せる。公開しない。ADR 0011）は結果確定のフォームの中に置き、formaction で送り先だけ変える（「もっと見る」で足した行もそのまま動く）。
 *
 * @var Answer $answer
 * @var bool   $isOpen 募集中か
 * @var string $slug
 */
?>
<li class="p-host__item" data-js-answer="<?= $answer->id ?>">
<?php if ($isOpen): ?>
<p class="p-host__body"><?= nl2br_esc($answer->body) ?></p>
<div class="p-host__item-meta">
<span class="u-sub"><?= esc($answer->posted_label ?? '') ?></span>
<button type="submit" formaction="<?= site_url('host/' . $slug . '/likes/' . $answer->id) ?>" class="p-host__like<?= $answer->is_favorite ? ' is-on' : '' ?>" aria-pressed="<?= $answer->is_favorite ? 'true' : 'false' ?>"><?= $this->include('partials/icon_heart') ?><span><?= $answer->is_favorite ? 'いいね済み' : 'いいね' ?></span></button>
</div>
<?php else: ?>
<label class="p-host__pick">
<input type="checkbox" class="u-visually-hidden" name="ranks[]" value="<?= $answer->id ?>" data-js-pick<?= $answer->rank !== null ? ' checked' : '' ?>>
<span class="p-host__pick-badge" data-js-pick-badge></span>
<span class="p-host__pick-body"><span class="p-host__body"><?= nl2br_esc($answer->body) ?></span><span class="u-sub"><?= esc($answer->posted_label ?? '') ?><?= $answer->is_favorite ? ' ・ ♥ いいね' : '' ?></span></span>
</label>
<?php endif; ?>
</li>
