/**
 * OS の共有シート（Web Share API。BUG-003）。navigator.share が使えるブラウザでだけボタンを出す。
 * 送り終えたときだけ GA4 に share_complete を送る（取り消したとき・失敗したときは送らない）。
 */
import { ga } from './tracking.js';

export function initWebShare(root = document) {
  if (typeof navigator.share !== 'function') return;
  root.querySelectorAll('[data-js-web-share]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', async () => {
      const { jsShareUrl: url, jsShareTitle: title, jsShareType: type, jsShareOogiri: oogiri } = button.dataset;
      try {
        await navigator.share({ title, text: title, url });
        ga('share_complete', { method: 'web_share', share_type: type, oogiri });
      } catch (_) { /* 取り消し（AbortError）や失敗は何もしない */ }
    });
  });
}
