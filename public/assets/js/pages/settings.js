/**
 * A-10 運用設定：未保存の変更の表示、スイッチの文言、AI判定 OFF のときの薄表示（すべて表示の補助）
 */
const form = document.querySelector('[data-js-settings-form]');
if (form) {
  const dirty = form.querySelector('[data-js-settings-dirty]');
  const aiGroup = form.querySelector('[data-js-ai-group]');
  const aiSwitch = form.elements.ai_enabled;
  const initial = new FormData(form);
  const snapshot = (data) => [...data.entries()].filter(([k]) => k !== 'csrf_test_name' && !k.startsWith('csrf')).map(([k, v]) => `${k}=${v}`).join('&');
  const initialState = snapshot(initial);

  const updateSwitchLabels = () => {
    form.querySelectorAll('[data-js-switch-on]').forEach((input) => {
      const label = input.closest('label')?.querySelector('[data-js-switch-label]');
      if (label) label.textContent = input.checked ? input.dataset.jsSwitchOn : input.dataset.jsSwitchOff;
    });
  };
  const update = () => {
    const isDirty = snapshot(new FormData(form)) !== initialState;
    if (dirty) {
      dirty.textContent = isDirty ? dirty.dataset.jsDirtyText : dirty.dataset.jsCleanText;
      dirty.classList.toggle('is-dirty', isDirty);
    }
    if (aiGroup && aiSwitch) aiGroup.classList.toggle('is-disabled', !aiSwitch.checked);
    updateSwitchLabels();
  };

  form.addEventListener('input', update);
  form.addEventListener('change', update);
  form.addEventListener('reset', () => setTimeout(update, 0));
  update();
}
