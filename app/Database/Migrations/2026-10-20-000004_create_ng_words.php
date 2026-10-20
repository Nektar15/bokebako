<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * NGワード（SPEC 3.3 ng_words）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateNgWords extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'word'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'match_type' => ['type' => 'VARCHAR', 'constraint' => 8],  // NgWordMatchType
            'category'   => ['type' => 'VARCHAR', 'constraint' => 32],
            'action'     => ['type' => 'VARCHAR', 'constraint' => 8],  // NgWordAction
            'enabled'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['word', 'match_type']);
        $this->forge->addKey('enabled');
        $this->forge->createTable('ng_words');
    }

    public function down(): void
    {
        $this->forge->dropTable('ng_words');
    }
}
