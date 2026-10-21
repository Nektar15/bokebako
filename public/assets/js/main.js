/**
 * JS の入口。各部品の初期化関数をここでまとめて呼ぶ（CODING_RULES 9-2）。
 * 要素の取得は data-js-* 属性だけで行い、CSS のクラス名を JS から参照しない。
 */
import { initToast } from './components/toast.js';
import { initCharCounter } from './components/char-counter.js';
import { initCopyButton } from './components/copy-button.js';
import { initConfirmDialog } from './components/confirm-dialog.js';
import { initTracking } from './components/tracking.js';
import { initReport } from './components/report.js';
import { initAutosubmit } from './components/autosubmit.js';
import { initScreenNav } from './components/screen-nav.js';
import { initAdminMenu } from './components/admin-menu.js';

const root = document;

initToast(root);
initCharCounter(root);
initCopyButton(root);
initConfirmDialog(root);
initTracking(root);
initReport(root);
initAutosubmit(root);
initScreenNav(root);
initAdminMenu(root);
