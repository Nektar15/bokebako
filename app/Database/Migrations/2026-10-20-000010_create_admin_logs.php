<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 操作履歴（CODING_RULES 7-1：誰が・いつ・何をしたか）。お問い合わせの対応メモもここ
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateAdminLogs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'actor'       => ['type' => 'VARCHAR', 'constraint' => 64], // 管理者名、「開催者」、「自動」
            'admin_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'target_type' => ['type' => 'VARCHAR', 'constraint' => 16], // oogiri / answer / inquiry / setting / ng_word / ad / ogp
            'target_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'what'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'created_at'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['target_type', 'target_id', 'created_at']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('admin_logs');
    }

    public function down(): void
    {
        $this->forge->dropTable('admin_logs');
    }
}
