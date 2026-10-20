/**
 * 通報（S-04）。開くボタンの data-js-report-type / data-js-report-id をモーダルに写し、/api/v1/reports へ送る。
 */
import { api } from '../lib/api.js';
import { showToast } from './toast.js';

export function initReport(root = document) {
  const form = root.querySelector('[data-js-report-form]');
  if (!form) return;
  const typeField = form.querySelector('[data-js-report-type-field]');
  const idField = form.querySelector('[data-js-report-id-field]');

  root.querySelectorAll('[data-js-report-type]').forEach((opener) => {
    opener.addEventListener('click', () => {
      typeField.value = opener.dataset.jsReportType;
      idField.value = opener.dataset.jsReportId;
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());
    data.slug = form.dataset.jsReportSlug;
    try {
      await api('/api/v1/reports', { method: 'POST', body: data });
      showToast('通報を受け付けました');
    } catch (err) {
      showToast(err.message);
    } finally {
      form.closest('dialog')?.close();
      form.reset();
    }
  });
}
