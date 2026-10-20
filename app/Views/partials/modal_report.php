<?php
/**
 * 通報モーダル（お題・回答共通）。開くボタンに data-js-report-type / data-js-report-id を付ける。
 * 送信は JS（components/report.js）が /api/v1/reports へ行う。
 *
 * @var string $slug
 */
?>
<dialog class="c-sheet" id="report-dialog" aria-labelledby="report-title">
<form class="c-sheet__form" data-js-report-form data-js-report-slug="<?= esc($slug, 'attr') ?>">
<div class="c-sheet__handle" aria-hidden="true"></div>
<h2 class="c-sheet__title" id="report-title">この投稿を通報する</h2>
<input type="hidden" name="type" value="answer" data-js-report-type-field>
<input type="hidden" name="id" value="" data-js-report-id-field>
<fieldset class="c-choice">
<legend class="c-field__label">理由</legend>
<label class="c-choice__item"><input type="radio" name="reason" value="abuse" required> 特定の人への攻撃・いじめ</label>
<label class="c-choice__item"><input type="radio" name="reason" value="personal"> 個人情報</label>
<label class="c-choice__item"><input type="radio" name="reason" value="discrimination"> 差別・脅迫</label>
<label class="c-choice__item"><input type="radio" name="reason" value="sexual"> 過度に性的な内容</label>
<label class="c-choice__item"><input type="radio" name="reason" value="rights"> 権利侵害・なりすまし</label>
<label class="c-choice__item"><input type="radio" name="reason" value="spam"> スパム・宣伝</label>
<label class="c-choice__item"><input type="radio" name="reason" value="other"> その他</label>
</fieldset>
<p class="c-field__help">通報したことが投稿した人に伝わることはありません。</p>
<div class="c-sheet__actions">
<button type="submit" class="c-button c-button--primary">通報する</button>
<button type="button" class="c-button c-button--ghost" data-js-close-dialog>やめる</button>
</div>
</form>
</dialog>
