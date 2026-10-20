/**
 * U-08（合言葉入力）：Cookie は消えたが localStorage に開催者トークンが残っていれば、サーバーに送って Cookie を復元し、開催者ページを開き直す。
 * 一度だけ試す（復元後に戻ってきたら ?restored=1 が付くので、そのときは合言葉の入力に任せる）。
 */
import { storage, keys } from '../lib/storage.js';
import { readConfig } from '../lib/api.js';

const config = readConfig('host');
const form = document.querySelector('[data-js-restore-form]');
if (config && config.slug && form && !new URLSearchParams(window.location.search).has('restored')) {
  const token = storage.get(keys.host(config.slug));
  if (token) {
    form.querySelector('[data-js-restore-token]').value = token;
    form.submit();
  }
}
