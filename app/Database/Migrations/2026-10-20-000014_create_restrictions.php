<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 投稿元の制限（S-03・A-05「投稿元を制限」）。until を過ぎたら無効
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateRestrictions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'browser_id_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'ip_hash'         => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'until'           => ['type' => 'DATETIME'],
            'reason'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['browser_id_hash', 'until']);
        $this->forge->addKey(['ip_hash', 'until']);
        $this->forge->createTable('restrictions');
    }

    public function down(): void
    {
        $this->forge->dropTable('restrictions');
    }
}
