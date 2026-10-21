/**
 * 画面一覧ナビ（確認用。ADR 0009）：背景のクリック・×・Esc で閉じる（開け閉めと移動は JS なしでも動く）
 */
export function initScreenNav(root = document) {
  root.querySelectorAll('[data-js-screen-nav]').forEach((details) => {
    details.querySelectorAll('[data-js-screen-nav-close]').forEach((el) => {
      el.addEventListener('click', () => { details.open = false; });
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') details.open = false;
    });
  });
}
