/**
 * 計測（S-06・ADR 0015）。
 *
 * 1. 自前の API（/api/v1/events）：<a data-js-track="event_name" data-js-track-oogiri="slug"> のクリックと、
 *    ページキャッシュで配信されるページ（<meta name="bk:pv">）の pv。event_name は App\Enums\EventName の値。
 *    管理画面の KPI（A-02）が使う。ページ遷移を止めないよう sendBeacon を使う。
 * 2. GA4：指示書 第9章のイベント名で送る（自前の名前とは別）。測定 ID がなければ何も送らない。
 *    - クリック：data-js-track の share_* は share_x_click（share_type 付き）。data-js-ga="名前" は GA4 だけに送る
 *    - 入力の開始：data-js-ga-start="名前" の欄に最初に入力したとき1回
 *    - 表示時：<script type="application/json" data-js-ga-page>{"name", "params", "once"}</script>
 *      once があれば、同じタブで二度送らない（再読み込みで開催完了・回答完了が二重にならないように）
 *    合言葉・回答の本文など、個人や秘密の情報はパラメータに入れない。
 * window.gtag だけは GA4 の仕様上グローバルに置く必要がある（CODING_RULES 9-2 の例外。ここ以外で window に書かない）。
 */
import { readConfig } from '../lib/api.js';

const SHARE_TYPES = { share_recruit: 'recruit', share_answer: 'answer', share_result: 'result' };

/** GA4 にイベントを送る。測定 ID がなければ何もしない */
export function ga(name, params = {}) {
  if (window.gtag) window.gtag('event', name, params);
}

function onceInTab(key) {
  try {
    const k = `bk:ga:${key}`;
    if (window.sessionStorage.getItem(k)) return false;
    window.sessionStorage.setItem(k, '1');
  } catch (_) { /* 使えなければ毎回送る */ }
  return true;
}

export function initTracking(root = document) {
  const ga4 = readConfig('ga4');
  if (ga4 && window.gtag === undefined) {
    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', ga4.id); // page_view は GA4 が自動で送る
  }

  const send = (payload) => {
    try {
      navigator.sendBeacon('/api/v1/events', new Blob([JSON.stringify(payload)], { type: 'application/json' }));
    } catch (_) { /* 計測は失敗しても画面の動作を止めない */ }
  };

  const pv = document.querySelector('meta[name="bk:pv"]');
  if (pv && pv.content) send({ name: 'pv', slug: pv.content });

  root.querySelectorAll('script[data-js-ga-page]').forEach((el) => {
    let event = null;
    try { event = JSON.parse(el.textContent); } catch (_) { return; }
    if (event && event.name && (!event.once || onceInTab(`${event.name}:${event.once}`))) ga(event.name, event.params || {});
  });

  root.querySelectorAll('[data-js-track]').forEach((el) => {
    el.addEventListener('click', () => {
      const name = el.dataset.jsTrack;
      const slug = el.dataset.jsTrackOogiri || null;
      send({ name, slug, answerId: el.dataset.jsTrackAnswer || null, from: el.dataset.jsTrackFrom || null });
      if (SHARE_TYPES[name]) ga('share_x_click', { share_type: SHARE_TYPES[name], oogiri: slug });
      else ga(name, { oogiri: slug });
    });
  });

  root.querySelectorAll('[data-js-ga]').forEach((el) => {
    el.addEventListener('click', () => {
      const params = {};
      Object.keys(el.dataset).forEach((k) => {
        if (k.startsWith('jsGaParam')) params[k.slice(9).replace(/^./, (c) => c.toLowerCase())] = el.dataset[k];
      });
      ga(el.dataset.jsGa, params);
    });
  });

  root.querySelectorAll('[data-js-ga-start]').forEach((el) => {
    el.addEventListener('input', () => ga(el.dataset.jsGaStart, { oogiri: el.dataset.jsGaOogiri || undefined }), { once: true });
  });
}
