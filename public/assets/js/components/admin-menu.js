/**
 * 管理画面の引き出しメニュー（1024px 未満）：キーボードの Enter・Space で開け閉めし、Esc で閉じる
 * 開け閉めそのものはチェックボックスと <label> で動く（JS がなくても動く）
 */
export function initAdminMenu(root = document) {
  const toggle = root.querySelector('[data-js-admin-menu]');
  if (!toggle) return;
  root.querySelectorAll('[data-js-admin-menu-button]').forEach((button) => {
    button.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle.checked = !toggle.checked;
      }
    });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') toggle.checked = false;
  });
}
