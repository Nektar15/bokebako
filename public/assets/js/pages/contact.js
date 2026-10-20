/** U-18：種別によって「対象のURL」を必須に切り替える（表示の補助。検証はサーバー側でも行う） */
const form = document.querySelector('[data-js-contact-form]');
if (form) {
  const required = (form.dataset.jsUrlRequired || '').split(',');
  const url = form.querySelector('[data-js-url-input]');
  const badge = form.querySelector('[data-js-url-req]');
  const update = () => {
    const type = form.querySelector('input[name="type"]:checked')?.value;
    const need = required.includes(type);
    url.required = need;
    badge.classList.toggle('u-hidden', !need);
    form.querySelectorAll('.p-contact__chip').forEach((c) => c.classList.toggle('is-on', c.querySelector('input').checked));
  };
  form.querySelectorAll('input[name="type"]').forEach((r) => r.addEventListener('change', update));
  update();
}
