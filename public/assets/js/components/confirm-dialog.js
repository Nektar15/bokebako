/**
 * 確認モーダル（U-02・U-05・U-09・削除確認）
 * <button data-js-open-dialog="dialog-id"> で <dialog id="dialog-id"> を開く。
 * 中の <button data-js-close-dialog> で閉じる。送信ボタンは <form> の submit で、二重送信を防ぐ。
 * <dialog> が使えない環境では、ボタンは通常の submit として動く（JS なしでも送信できる）。
 */
export function initConfirmDialog(root = document) {
  root.querySelectorAll('[data-js-open-dialog]').forEach((opener) => {
    const dialog = document.getElementById(opener.dataset.jsOpenDialog);
    if (!dialog || typeof dialog.showModal !== 'function') return;
    opener.addEventListener('click', (e) => {
      const form = opener.form;
      if (form && !form.reportValidity()) return;
      e.preventDefault();
      dialog.showModal();
    });
  });

  root.querySelectorAll('[data-js-close-dialog]').forEach((closer) => {
    closer.addEventListener('click', () => closer.closest('dialog')?.close());
  });

  root.querySelectorAll('form[data-js-once]').forEach((form) => {
    form.addEventListener('submit', () => {
      form.querySelectorAll('button[type="submit"]').forEach((b) => { b.disabled = true; });
    });
  });
}
