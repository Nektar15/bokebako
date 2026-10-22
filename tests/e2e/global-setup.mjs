// 開発用データを入れ直し、連続投稿の制限を緩める（App\Database\Seeds\E2eSeeder）。E2E_SKIP_SEED=1 で入れ直さない
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

export default function globalSetup() {
  if (process.env.E2E_SKIP_SEED) return;
  const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
  execFileSync('php', ['spark', 'db:seed', 'E2eSeeder'], { cwd: root, stdio: 'inherit' });
}
