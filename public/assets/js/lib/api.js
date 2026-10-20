/**
 * サーバーとの通信はこの関数だけを使う（CODING_RULES 9-2）。
 * API（/api/v1/*）は CSRF トークンの代わりに X-Requested-With を見る（ページキャッシュ配信のページでも動くように。ADR 0005）。
 * レスポンスの形は CODING_RULES 11章。
 */

/**
 * @param {string} path   例: '/api/v1/reports'
 * @param {{method?: string, body?: object}} options
 * @returns {Promise<any>} data。失敗時は {code, message} を持つ Error を投げる
 */
export async function api(path, { method = 'GET', body } = {}) {
  const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }
  const res = await fetch(path, { method, headers, body: body === undefined ? undefined : JSON.stringify(body), credentials: 'same-origin' });
  let json = null;
  try { json = await res.json(); } catch (_) { /* JSON でない応答はサーバーエラー扱い */ }

  if (!res.ok || !json || json.ok !== true) {
    const err = new Error((json && json.error && json.error.message) || 'エラーが発生しました');
    err.code = (json && json.error && json.error.code) || 'SERVER_ERROR';
    err.status = res.status;
    throw err;
  }
  return json.data;
}

/** 画面の設定値（<script type="application/json" data-js-config="名前">）を読む */
export function readConfig(name) {
  const el = document.querySelector(`script[data-js-config="${name}"]`);
  if (!el) return null;
  try { return JSON.parse(el.textContent); } catch (_) { return null; }
}

/**
 * 同じサイトの HTML ページを取りに行き、Document にして返す（「もっと見る」で続きの行を足すとき）
 * @param {string} url
 * @returns {Promise<Document>}
 */
export async function fetchPage(url) {
  const res = await fetch(url, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
  if (!res.ok) throw new Error('読み込めませんでした');
  return new DOMParser().parseFromString(await res.text(), 'text/html');
}
