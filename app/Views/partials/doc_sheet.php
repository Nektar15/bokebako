<?php
/**
 * 規約・ガイドラインをその場で読むシート（BUG-007、ADR 0015）。開催確認（U-02）の上に重ねて開く
 *
 * <a data-js-doc-sheet="doc-dialog" data-js-doc-name="terms" href="/terms" target="_blank"> を押すと、JS がページを取りに行き、
 * 本文だけをここに入れて開く。閉じると U-02 に戻る（入力も U-02 もそのまま）。
 * JS が動かない環境では、リンクが別のタブで開く（元のタブの入力は消えない）。
 */
?>
<dialog class="c-sheet c-sheet--doc" id="doc-dialog" aria-labelledby="doc-dialog-title">
<div class="c-sheet__form">
<div class="c-sheet__handle" aria-hidden="true"></div>
<div class="c-sheet__doc-head">
<h2 class="c-sheet__doc-title" id="doc-dialog-title" data-js-doc-title>読み込み中…</h2>
<button type="button" class="c-sheet__close" data-js-close-dialog aria-label="閉じる">×</button>
</div>
<div class="c-sheet__doc-body" data-js-doc-body tabindex="0"></div>
<button type="button" class="c-button c-button--secondary" data-js-close-dialog>開催の確認に戻る</button>
</div>
</dialog>
