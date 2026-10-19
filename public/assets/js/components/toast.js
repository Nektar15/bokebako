/** トースト表示。showToast('コピーしました') で使う */
let el = null;
let timer = null;

export function initToast(root = document) {
  el = root.querySelector('[data-js-toast]');
}

export function showToast(message, ms = 1800) {
  if (!el) return;
  el.textContent = message;
  el.hidden = false;
  clearTimeout(timer);
  timer = setTimeout(() => { el.hidden = true; }, ms);
}
