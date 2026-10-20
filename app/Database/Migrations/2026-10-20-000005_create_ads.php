<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 広告（SPEC 3.3 ads）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateAds extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'sponsor'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'image'       => ['type' => 'VARCHAR', 'constraint' => 255], // public/ からの相対パス
            'url'         => ['type' => 'VARCHAR', 'constraint' => 1000],
            'slot'        => ['type' => 'VARCHAR', 'constraint' => 8],  // AdSlot
            'status'      => ['type' => 'VARCHAR', 'constraint' => 12], // AdStatus（draft / published / stopped。live・scheduled・ended は期間から判定）
            'starts_at'   => ['type' => 'DATETIME'],
            'ends_at'     => ['type' => 'DATETIME'],
            'impressions' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'clicks'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['slot', 'status', 'starts_at', 'ends_at']);
        $this->forge->createTable('ads');
    }

    public function down(): void
    {
        $this->forge->dropTable('ads');
    }
}
