/**
 * 計測（S-06）。<a data-js-track="event_name" data-js-track-oogiri="slug"> のクリックを記録する。
 * 自前の API（/api/v1/events）と GA4 の両方へ同名イベントを送る。
 * ページ遷移を止めないよう sendBeacon を使う。
 * window.gtag だけは GA4 の仕様上グローバルに置く必要がある（CODING_RULES 9-2 の例外。ここ以外で window に書かない）。
 */
import { readConfig } from '../lib/api.js';

export function initTracking(root = document) {
  const ga4 = readConfig('ga4');
  if (ga4 && window.gtag === undefined) {
    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', ga4.id);
  }

  root.querySelectorAll('[data-js-track]').forEach((el) => {
    el.addEventListener('click', () => {
      const payload = {
        event: el.dataset.jsTrack,
        oogiri: el.dataset.jsTrackOogiri || null,
        answer: el.dataset.jsTrackAnswer || null,
        from: el.dataset.jsTrackFrom || null,
      };
      try {
        navigator.sendBeacon('/api/v1/events', new Blob([JSON.stringify(payload)], { type: 'application/json' }));
      } catch (_) { /* 計測は失敗しても画面の動作を止めない */ }
      if (window.gtag) window.gtag('event', payload.event, { oogiri: payload.oogiri });
    });
  });
}
