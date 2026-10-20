/** U-06：回答済みの印をブラウザに残す（U-07 の表示判定の片方。もう片方はサーバーの投稿記録） */
import { storage, keys } from '../lib/storage.js';
import { readConfig } from '../lib/api.js';

const config = readConfig('answered');
if (config && config.slug) storage.set(keys.answered(config.slug), '1');
