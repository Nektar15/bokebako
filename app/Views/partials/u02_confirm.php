<?php
/**
 * U-02 開催確認モーダル。U-01 の <form> の中に置く（送信ボタンは同じフォームを submit する）
 * JS が動かない環境では <dialog> が開かず、親フォームがそのまま送信される。
 */
?>
<dialog class="c-sheet" id="confirm-dialog" aria-labelledby="confirm-title">
<div class="c-sheet__form">
<div class="c-sheet__handle" aria-hidden="true"></div>
<h2 class="c-sheet__title" id="confirm-title">この内容で開催しますか？</h2>
<div class="c-card c-card--odai c-sheet__odai"><span class="c-card__tab">お題</span><p class="c-odai" data-js-odai-preview></p></div>
<ul class="c-sheet__points">
<li><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D6401E" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7 V12 L15.5 14"></path></svg><span>このお題は公開から<b>24時間</b>、匿名回答を募集します。</span></li>
<li><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D6401E" stroke-width="2.2" aria-hidden="true"><path d="M8 4 H16 V9 A4 4 0 0 1 8 9 Z"></path><path d="M8 6 H5 A3 3 0 0 0 8 10.5 M16 6 H19 A3 3 0 0 1 16 10.5 M12 13 V17 M8 21 H16 M9.5 17 H14.5"></path></svg><span>募集終了後、あなたが気に入った回答を<b>最大3件</b>選べます。</span></li>
</ul>
<div class="c-sheet__note">
<span class="c-sheet__note-title">みんなが楽しく遊ぶために</span>
<ul class="c-sheet__rules">
<li>特定の人への攻撃はしない</li>
<li>名前や住所などの個人情報は書かない</li>
<li>違法なもの・他人の権利を侵害するものは書かない</li>
<li>問題のある投稿は非表示・削除することがあります</li>
</ul>
<div class="c-sheet__links"><a href="<?= site_url('terms') ?>" target="_blank" rel="noopener" data-js-doc-sheet="doc-dialog" data-js-doc-name="terms">利用規約</a><a href="<?= site_url('guidelines') ?>" target="_blank" rel="noopener" data-js-doc-sheet="doc-dialog" data-js-doc-name="guidelines">投稿ガイドライン</a></div>
</div>
<div class="c-sheet__actions">
<button type="submit" class="c-button c-button--primary">同意して開催する</button>
<button type="button" class="c-button c-button--ghost" data-js-close-dialog>戻って修正する</button>
</div>
</div>
</dialog>
