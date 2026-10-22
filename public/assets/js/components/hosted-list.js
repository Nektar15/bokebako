/**
 * このブラウザで開催した大喜利の一覧（D-02）。localStorage の開催者トークン（bk:host:{slug}）を送り、今も正しいものだけ出す。
 * 1件もなければ枠ごと出さない。
 */
import { api } from '../lib/api.js';

const PREFIX = 'bk:host:';

function hostTokens() {
  const items = [];
  try {
    for (let i = 0; i < window.localStorage.length; i++) {
      const key = window.localStorage.key(i);
      if (key && key.startsWith(PREFIX)) items.push({ slug: key.slice(PREFIX.length), token: window.localStorage.getItem(key) });
    }
  } catch (_) { /* localStorage が使えなければ一覧は出さない */ }
  return items;
}

export async function initHostedList(root = document) {
  const section = root.querySelector('[data-js-hosted]');
  if (!section) return;
  const except = section.dataset.jsHostedExcept || '';
  const items = hostTokens().filter((item) => item.slug !== except).slice(0, 20);
  if (!items.length) return;

  let data;
  try {
    data = await api('/api/v1/hosted', { method: 'POST', body: { items } });
  } catch (_) {
    return;
  }
  if (!data.items.length) return;

  const list = section.querySelector('[data-js-hosted-list]');
  data.items.forEach((item) => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.className = 'c-hosted__item';
    a.href = `/host/${encodeURIComponent(item.slug)}`;
    const odai = document.createElement('span');
    odai.className = 'c-hosted__odai';
    odai.textContent = item.odai;
    const meta = document.createElement('span');
    meta.className = 'c-hosted__meta';
    meta.textContent = `${item.statusLabel}・${item.answerCount}回答・${item.startsAt}`;
    a.append(odai, meta);
    li.append(a);
    list.append(li);
  });
  section.hidden = false;
}
