<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * E2E テスト（tests/e2e。ADR 0015）用：開発用データを入れ直し、連続投稿の制限を緩める（1台のブラウザから何度も開催・回答するため。値は運用設定の上限内にする：A-10 の保存が入力チェックで弾かれないように）
 *
 * 開発環境だけで使う。本番・ステージングで流さない（DevSeeder と同じく全データを消す）
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            throw new RuntimeException('E2eSeeder は本番で流さない');
        }
        $this->call(DevSeeder::class);
        $this->db->table('settings')->insert(['name' => 'burst_count', 'value' => '100']);
        cache()->clean();
    }
}
