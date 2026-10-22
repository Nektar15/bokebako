/**
 * 規約・ガイドラインをシートで開く（BUG-007）。<a data-js-doc-sheet="dialog-id" href="/terms">
 * ページの本文（<main> の中。広告・計測・ページ内の「先頭へ」などを除く）を取り込んで表示する。
 * 取りに行けなかったときは、ふつうのリンクとして別のタブで開く。
 */
import { fetchPage } from '../lib/api.js';
import { ga } from './tracking.js';

const SKIP = 'script, .c-ad, [data-js-ad-slot], .p-doc__top, [data-js-doc-skip]';

export function initDocSheet(root = document) {
  root.querySelectorAll('[data-js-doc-sheet]').forEach((link) => {
    const dialog = document.getElementById(link.dataset.jsDocSheet);
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const title = dialog.querySelector('[data-js-doc-title]');
    const body = dialog.querySelector('[data-js-doc-body]');

    link.addEventListener('click', async (e) => {
      e.preventDefault();
      title.textContent = '読み込み中…';
      body.replaceChildren();
      body.setAttribute('aria-busy', 'true');
      dialog.showModal();
      try {
        const doc = await fetchPage(link.href);
        const main = doc.querySelector('main');
        if (!main) throw new Error('本文がありません');
        main.querySelectorAll(SKIP).forEach((el) => el.remove());
        const h1 = main.querySelector('h1');
        title.textContent = link.textContent.trim();
        if (h1) h1.remove();
        body.replaceChildren(...[...main.childNodes].map((n) => document.importNode(n, true)));
        body.removeAttribute('aria-busy');
        body.scrollTop = 0;
        body.focus();
        ga('guideline_view', { doc: link.dataset.jsDocName || '', via: 'modal' });
      } catch (_) {
        dialog.close();
        window.open(link.href, '_blank', 'noopener');
      }
    });
  });
}
