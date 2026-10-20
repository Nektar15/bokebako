/**
 * 選択したらすぐ送る（管理画面の絞り込みセレクト、CSV のファイル選択）
 * <select data-js-autosubmit> / <input type="file" data-js-autosubmit> の change でフォームを送信する。
 * JS が動かないときは <button data-js-hide-with-js> で送る（JS が動けばそのボタンは隠す）。
 */
export function initAutosubmit(root = document) {
  root.querySelectorAll('[data-js-autosubmit]').forEach((field) => {
    field.addEventListener('change', () => {
      if (field.form) field.form.requestSubmit();
    });
  });
  root.querySelectorAll('[data-js-hide-with-js]').forEach((button) => {
    button.hidden = true;
  });
}
