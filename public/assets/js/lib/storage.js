/**
 * ブラウザ保存。使うキーは CODING_RULES 9-2 の表に限る。
 *   bk:host:{slug}      開催者トークン
 *   bk:answered:{slug}  回答済みの印
 * localStorage が使えない環境でも落ちないよう try/catch で包む。
 */
export const storage = {
  get(key) {
    try { return window.localStorage.getItem(key); } catch (_) { return null; }
  },
  set(key, value) {
    try { window.localStorage.setItem(key, value); } catch (_) { /* 保存できなくても動作は続ける */ }
  },
  remove(key) {
    try { window.localStorage.removeItem(key); } catch (_) { /* 同上 */ }
  },
};

export const keys = {
  host: (slug) => `bk:host:${slug}`,
  answered: (slug) => `bk:answered:${slug}`,
};
