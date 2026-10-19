/** URL コピー。<button data-js-copy="コピーする文字列"> */
import { showToast } from './toast.js';

export function initCopyButton(root = document) {
  root.querySelectorAll('[data-js-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(button.dataset.jsCopy);
        showToast('コピーしました');
      } catch (_) {
        showToast('コピーできませんでした。URLを長押しして選択してください');
      }
    });
  });
}
