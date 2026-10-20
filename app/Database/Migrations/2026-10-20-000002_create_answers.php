<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 回答（SPEC 3.3 answers）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateAnswers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'oogiri_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'body'            => ['type' => 'VARCHAR', 'constraint' => 1000],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'visible'], // AnswerStatus
            'rank'            => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],       // 1〜3位
            'is_favorite'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'browser_id_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'ip_hash'         => ['type' => 'CHAR', 'constraint' => 64],
            'report_count'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['oogiri_id', 'status', 'created_at']);
        $this->forge->addKey(['oogiri_id', 'browser_id_hash']);
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('answers');
    }

    public function down(): void
    {
        $this->forge->dropTable('answers');
    }
}
