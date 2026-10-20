/** U-01：テンプレート選択・ランダム・確認モーダルへのお題反映 */
const input = document.querySelector('[data-js-odai-input]');
const preview = document.querySelector('[data-js-odai-preview]');
const templates = [...document.querySelectorAll('[data-js-template]')].map((b) => b.dataset.jsTemplate);

function setOdai(text) {
  if (!input) return;
  input.value = text;
  input.dispatchEvent(new Event('input'));
  input.focus();
}

document.querySelectorAll('[data-js-template]').forEach((button) => {
  button.addEventListener('click', () => setOdai(button.dataset.jsTemplate));
});

document.querySelector('[data-js-random-odai]')?.addEventListener('click', () => {
  if (templates.length) setOdai(templates[Math.floor(Math.random() * templates.length)]);
});

// 確認モーダルを開くたびに、入力中のお題を反映する
document.querySelector('[data-js-open-dialog="confirm-dialog"]')?.addEventListener('click', () => {
  if (preview && input) preview.textContent = input.value.trim();
});
