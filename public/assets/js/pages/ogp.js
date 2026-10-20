/** A-08：再生成の種類に合わせて、対象の見出しとプレースホルダーを切り替える（表示の補助） */
const form = document.querySelector('[data-js-ogp-form]');
if (form) {
  const label = form.querySelector('[data-js-ogp-target-label]');
  const input = form.querySelector('[data-js-ogp-target]');
  const update = () => {
    const kind = form.querySelector('[name="kind"]:checked')?.value;
    const isAnswer = kind === 'answer';
    if (label) label.textContent = isAnswer ? label.dataset.jsLabelAnswer : label.dataset.jsLabelOogiri;
    if (input) input.placeholder = isAnswer ? input.dataset.jsPhAnswer : input.dataset.jsPhOogiri;
  };
  form.querySelectorAll('[name="kind"]').forEach((r) => r.addEventListener('change', update));
  update();
}
