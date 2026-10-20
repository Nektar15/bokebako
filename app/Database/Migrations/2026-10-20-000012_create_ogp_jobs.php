<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * OGP 画像の生成記録（S-07、A-08）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateOgpJobs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'kind'       => ['type' => 'VARCHAR', 'constraint' => 8],  // OgpKind
            'target_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'target'     => ['type' => 'VARCHAR', 'constraint' => 255], // 表示用（例：「#12480 こんな…」「A-90412」）
            'actor'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'succeeded'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'error'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('ogp_jobs');
    }

    public function down(): void
    {
        $this->forge->dropTable('ogp_jobs');
    }
}
