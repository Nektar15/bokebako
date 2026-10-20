<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * お問い合わせ（SPEC 3.3 inquiries）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateInquiries extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 16],  // InquiryType
            'email'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'body'       => ['type' => 'TEXT'],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'todo'], // InquiryStatus
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('type');
        $this->forge->createTable('inquiries');
    }

    public function down(): void
    {
        $this->forge->dropTable('inquiries');
    }
}
