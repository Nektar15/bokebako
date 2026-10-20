/**
 * U-03：開催者トークンをブラウザに保存する（CODING_RULES 9-2 の保存キー表）
 * トークンは Cookie（サーバー発行）にもあるが、Cookie が消えたときの復旧用に localStorage にも持つ。
 */
import { storage, keys } from '../lib/storage.js';
import { readConfig } from '../lib/api.js';

const config = readConfig('host');
if (config && config.slug && config.token) {
  storage.set(keys.host(config.slug), config.token);
}

document.querySelector('[data-js-copy]')?.addEventListener('click', (e) => {
  const b = e.currentTarget;
  b.classList.add('is-done');
  b.textContent = 'コピー済み';
  setTimeout(() => { b.classList.remove('is-done'); b.textContent = 'コピー'; }, 1800);
});
