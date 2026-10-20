<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 計測イベントの生ログ（SPEC 3.3 events、S-06）。日次で daily_stats に集計する
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateEvents extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 32],  // EventName
            'oogiri_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'answer_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'parent_oogiri_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true], // 派生元（開催・新規開催クリック）
            'browser_id_hash'  => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'referer'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'utm'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'       => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['name', 'created_at']);
        $this->forge->addKey('created_at');
        $this->forge->addKey('oogiri_id');
        $this->forge->createTable('events');
    }

    public function down(): void
    {
        $this->forge->dropTable('events');
    }
}
