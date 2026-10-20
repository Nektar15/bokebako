<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 通報（SPEC 3.3 reports、S-04）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateReports extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'target_type'     => ['type' => 'VARCHAR', 'constraint' => 8],   // ReportTarget
            'target_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'reason'          => ['type' => 'VARCHAR', 'constraint' => 32],  // 通報理由（選択式）
            'browser_id_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'ip_hash'         => ['type' => 'CHAR', 'constraint' => 64],
            'resolved'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0], // 管理者が処理済みにしたか
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['target_type', 'target_id']);
        $this->forge->addKey('created_at');
        $this->forge->addKey('resolved');
        $this->forge->createTable('reports');
    }

    public function down(): void
    {
        $this->forge->dropTable('reports');
    }
}
