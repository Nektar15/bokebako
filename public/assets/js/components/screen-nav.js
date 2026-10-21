/**
 * 画面一覧ナビ（確認用。ADR 0009）：プルダウンで選んだら、その場で移動する
 */
export function initScreenNav(root = document) {
  root.querySelectorAll('[data-js-screen-nav]').forEach((form) => {
    const select = form.querySelector('[data-js-screen-nav-select]');
    select?.addEventListener('change', () => {
      if (select.value !== '') form.submit();
    });
  });
}
