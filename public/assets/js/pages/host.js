/**
 * U-08：ベスト回答の選択（タップした順に 1位・2位・3位、再タップで解除、最大3件）
 * チェックボックス（name="ranks[]"）が本体。JS なしでも 3件までの選択と送信はできる（順位は選んだ順＝DOM順になる）。
 * 送信時は選んだ順で hidden を並べ替え、サーバーは ranks[] の順を 1位から読む。
 */
import { storage, keys } from '../lib/storage.js';
import { fetchPage, readConfig } from '../lib/api.js';

// 開催者トークンを localStorage にも持つ（Cookie が消えたときの復旧用）
const hostConfig = readConfig('host');
if (hostConfig && hostConfig.slug && hostConfig.token) {
  storage.set(keys.host(hostConfig.slug), hostConfig.token);
}

const form = document.querySelector('[data-js-host-form]');
// 「もっと見る」で行が増えるので、選択肢は毎回取り直す
const allPicks = () => [...document.querySelectorAll('[data-js-pick]')];
if (form && allPicks().length) {
  const slots = [...document.querySelectorAll('[data-js-slots] [data-js-slot]')];
  const confirmSlots = [...document.querySelectorAll('[data-js-confirm-slots] li')];
  const decideBtn = document.querySelector('[data-js-decide-btn]');
  const countEl = document.querySelector('[data-js-pick-count]');
  let order = allPicks().filter((p) => p.checked).map((p) => p.value);

  const findPick = (id) => allPicks().find((p) => p.value === id);

  const render = () => {
    allPicks().forEach((p) => {
      const item = p.closest('[data-js-answer]');
      const idx = order.indexOf(p.value);
      p.checked = idx >= 0;
      item.classList.toggle('is-picked', idx >= 0);
      item.classList.toggle('is-first', idx === 0);
      item.querySelector('[data-js-pick-badge]').textContent = idx >= 0 ? `${idx + 1}位` : '';
    });
    const texts = order.map((id) => findPick(id).closest('[data-js-answer]').querySelector('.p-host__body').textContent.trim());
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

  // 後から足した行にも効くよう、フォームでまとめて受ける
  form.addEventListener('change', (e) => {
    const p = e.target.closest('[data-js-pick]');
    if (!p) return;
    const i = order.indexOf(p.value);
    if (i >= 0) order.splice(i, 1);
    else if (order.length < 3) order.push(p.value);
    render();
  });

  // 送信時：選んだ順に並ぶよう、チェック済みの input をフォーム末尾に移す
  form.addEventListener('submit', () => {
    order.forEach((id) => form.appendChild(findPick(id)));
  });

  render();
}

// 「もっと見る」：次のページの行を今の一覧に足す（選んだ順位を保つため、ページを移らない）。JS がなければ普通にページを移る
const more = document.querySelector('[data-js-host-more]');
const list = document.querySelector('[data-js-host-list]');
if (more && list) {
  more.addEventListener('click', async (e) => {
    e.preventDefault();
    more.setAttribute('aria-busy', 'true');
    try {
      const doc = await fetchPage(more.href);
      const known = new Set([...document.querySelectorAll('[data-js-answer]')].map((el) => el.dataset.jsAnswer));
      doc.querySelectorAll('[data-js-host-list] [data-js-answer]').forEach((item) => {
        if (!known.has(item.dataset.jsAnswer)) list.appendChild(document.importNode(item, true));
      });
      const next = doc.querySelector('[data-js-host-more]');
      if (next) more.href = next.href;
      else more.remove();
    } catch (_) {
      window.location.href = more.href;
    } finally {
      more.removeAttribute('aria-busy');
    }
  });
}

// 削除などの確認（data-js-confirm="文言"）
document.querySelectorAll('form[data-js-confirm]').forEach((f) => {
  f.addEventListener('submit', (e) => {
    if (!window.confirm(f.dataset.jsConfirm)) e.preventDefault();
  });
});
