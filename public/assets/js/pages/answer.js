/**
 * U-04：確認モーダルへのボケ反映、残り時間・回答数の1分ごとの更新（軽量 API）
 * 0分になったら入力を止めて案内を出す。締切の最終判定はサーバー側（S-01）。
 */
import { api } from '../lib/api.js';

const input = document.querySelector('[data-js-answer-input]');
const preview = document.querySelector('[data-js-answer-preview]');
document.querySelector('[data-js-answer-submit]')?.addEventListener('click', () => {
  if (preview && input) preview.textContent = input.value.trim();
});

const timer = document.querySelector('[data-js-timer]');
if (timer) {
  const slug = timer.dataset.jsTimer;
  const remaining = timer.querySelector('[data-js-timer-remaining]');
  const bar = timer.querySelector('[data-js-timer-bar]');
  const count = document.querySelector('[data-js-timer-count]');

  const label = (sec) => {
    if (sec <= 0) return '0分';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    return (h > 0 ? `${h}時間` : '') + `${m}分`;
  };

  const close = () => {
    timer.classList.add('is-closed');
    if (input) input.disabled = true;
    const submit = document.querySelector('[data-js-answer-submit]');
    if (submit) submit.disabled = true;
    document.querySelector('[data-js-closed-note]')?.classList.remove('u-hidden');
  };

  const refresh = async () => {
    try {
      const d = await api(`/api/v1/o/${encodeURIComponent(slug)}/status`);
      if (remaining) remaining.textContent = label(d.remainingSeconds);
      if (bar && d.progressPercent !== undefined) bar.value = d.progressPercent;
      if (count && d.answerCount !== undefined) count.textContent = String(d.answerCount);
      if (d.status !== 'open' || d.remainingSeconds <= 0) close();
    } catch (_) { /* 取れなくても画面は表示したまま */ }
  };

  setInterval(refresh, 60 * 1000);
}
