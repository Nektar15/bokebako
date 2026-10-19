/**
 * 文字数カウンター（表示の補助。上限の検証はサーバー側で必ず行う）
 * <textarea data-js-char-counter="counter-id" maxlength="100"> と <span id="counter-id">
 */
export function initCharCounter(root = document) {
  root.querySelectorAll('[data-js-char-counter]').forEach((input) => {
    const out = root.getElementById ? root.getElementById(input.dataset.jsCharCounter) : document.getElementById(input.dataset.jsCharCounter);
    if (!out) return;
    const max = Number(input.getAttribute('maxlength')) || 0;
    const update = () => { out.textContent = `${[...input.value].length} / ${max}`; };
    input.addEventListener('input', update);
    update();
  });
}
