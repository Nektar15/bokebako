import { api } from '../lib/api.js';

/**
 * ページキャッシュで配信するページ（U-10・U-12）の広告枠：表示のたびに /api/v1/ads から取って差し込む
 * （HTML に焼き込むと、ローテーション・表示回数・停止が効かない。監査 2026-10-21）
 */
export async function initAdSlots(root = document) {
  const slots = root.querySelectorAll('[data-js-ad-slot]');
  if (slots.length === 0) return;
  let data;
  try {
    data = await api('/api/v1/ads');
  } catch (_) {
    return; // 広告が出ないだけ
  }
  slots.forEach((slot) => {
    const ad = data[slot.dataset.jsAdSlot];
    if (!ad) return;
    const link = document.createElement('a');
    link.className = 'c-ad__link';
    link.href = ad.href;
    link.rel = 'sponsored nofollow';
    link.target = '_blank';
    const pr = document.createElement('span');
    pr.className = 'c-ad__pr';
    pr.textContent = 'PR';
    const img = document.createElement('img');
    img.className = 'c-ad__image';
    img.src = ad.image;
    img.alt = ad.alt;
    img.width = 320;
    img.height = 50;
    link.append(pr, img);
    slot.append(link);
    slot.hidden = false;
  });
}
