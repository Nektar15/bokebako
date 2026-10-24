/**
 * 伏せ字の入力欄の「表示」ボタン（合言葉。BUG-002）
 * <button data-js-reveal="input-id"> を押すたびに、入力欄の type を password と text で切り替える。
 */
export function initReveal(root = document) {
  root.querySelectorAll('[data-js-reveal]').forEach((button) => {
    const input = document.getElementById(button.dataset.jsReveal);
    if (!input) return;
    button.addEventListener('click', () => {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.textContent = show ? '隠す' : '表示';
      button.setAttribute('aria-pressed', String(show));
    });
  });
}
