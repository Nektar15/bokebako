/**
 * U-08：ベスト回答の選択（タップした順に 1位・2位・3位、再タップで解除、最大3件）
 * チェックボックス（name="ranks[]"）が本体。JS なしでも 3件までの選択と送信はできる（順位は選んだ順＝DOM順になる）。
 * 送信時は選んだ順で hidden を並べ替え、サーバーは ranks[] の順を 1位から読む。
 */
import { storage, keys } from '../lib/storage.js';
import { readConfig } from '../lib/api.js';

// 開催者トークンを localStorage にも持つ（Cookie が消えたときの復旧用）
const hostConfig = readConfig('host');
if (hostConfig && hostConfig.slug && hostConfig.token) {
  storage.set(keys.host(hostConfig.slug), hostConfig.token);
}

const form = document.querySelector('[data-js-host-form]');
const picks = [...document.querySelectorAll('[data-js-pick]')];
if (form && picks.length) {
  const slots = [...document.querySelectorAll('[data-js-slots] [data-js-slot]')];
  const confirmSlots = [...document.querySelectorAll('[data-js-confirm-slots] li')];
  const decideBtn = document.querySelector('[data-js-decide-btn]');
  const countEl = document.querySelector('[data-js-pick-count]');
  let order = picks.filter((p) => p.checked).map((p) => p.value);

  const render = () => {
    picks.forEach((p) => {
      const item = p.closest('[data-js-answer]');
      const idx = order.indexOf(p.value);
      p.checked = idx >= 0;
      item.classList.toggle('is-picked', idx >= 0);
      item.classList.toggle('is-first', idx === 0);
      item.querySelector('[data-js-pick-badge]').textContent = idx >= 0 ? `${idx + 1}位` : '';
    });
    const texts = order.map((id) => picks.find((p) => p.value === id).closest('[data-js-answer]').querySelector('.p-host__body').textContent.trim());
    slots.forEach((slot, i) => {
      slot.classList.toggle('is-filled', i < texts.length);
      slot.querySelector('[data-js-slot-text]').textContent = texts[i] || 'まだ選ばれていません';
    });
    confirmSlots.forEach((slot, i) => {
      slot.classList.toggle('is-filled', i < texts.length);
      slot.querySelector('.p-host__slot-text').textContent = texts[i] || 'なし';
    });
    if (decideBtn) decideBtn.disabled = order.length === 0;
    if (countEl) countEl.textContent = `${order.length}件`;
  };

  picks.forEach((p) => {
    p.addEventListener('change', () => {
      const i = order.indexOf(p.value);
      if (i >= 0) order.splice(i, 1);
      else if (order.length < 3) order.push(p.value);
      render();
    });
  });

  // 送信時：選んだ順に並ぶよう、チェック済みの input をフォーム末尾に移す
  form.addEventListener('submit', () => {
    order.forEach((id) => form.appendChild(picks.find((p) => p.value === id)));
  });

  render();
}

// 削除などの確認（data-js-confirm="文言"）
document.querySelectorAll('form[data-js-confirm]').forEach((f) => {
  f.addEventListener('submit', (e) => {
    if (!window.confirm(f.dataset.jsConfirm)) e.preventDefault();
  });
});
