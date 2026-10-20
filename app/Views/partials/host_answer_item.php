<?php
/**
 * U-08 開催者画面の回答1件（募集中：お気に入りボタン、結果選択待ち：ベスト回答の選択）
 *
 * お気に入りは結果確定のフォームの中に置き、formaction で送り先だけ変える（「もっと見る」で足した行もそのまま動く）。
 *
 * @var \App\Entities\Answer $answer
 * @var bool   $isOpen 募集中か
 * @var string $slug
 */
?>
<li class="p-host__item" data-js-answer="<?= $answer->id ?>">
<?php if ($isOpen): ?>
<p class="p-host__body"><?= nl2br_esc($answer->body) ?></p>
<div class="p-host__item-meta">
<span class="u-sub"><?= esc($answer->posted_label ?? '') ?></span>
<button type="submit" formaction="<?= site_url('host/' . $slug . '/favorites/' . $answer->id) ?>" class="p-host__fav<?= $answer->is_favorite ? ' is-on' : '' ?>" aria-pressed="<?= $answer->is_favorite ? 'true' : 'false' ?>" aria-label="お気に入り">★</button>
</div>
<?php else: ?>
<label class="p-host__pick">
<input type="checkbox" class="u-visually-hidden" name="ranks[]" value="<?= $answer->id ?>" data-js-pick<?= $answer->rank !== null ? ' checked' : '' ?>>
<span class="p-host__pick-badge" data-js-pick-badge></span>
<span class="p-host__pick-body"><span class="p-host__body"><?= nl2br_esc($answer->body) ?></span><span class="u-sub"><?= esc($answer->posted_label ?? '') ?><?= $answer->is_favorite ? ' ・ ★お気に入り' : '' ?></span></span>
</label>
<?php endif; ?>
</li>
