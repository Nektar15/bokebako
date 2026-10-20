<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 大喜利（SPEC 3.3 oogiri）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateOogiris extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'               => ['type' => 'VARCHAR', 'constraint' => 16],   // 公開URL用（random_bytes から生成）
            'odai'               => ['type' => 'VARCHAR', 'constraint' => 500],
            'host_token_hash'    => ['type' => 'CHAR', 'constraint' => 64],      // hash_hmac sha256（CODING_RULES 7章）
            'passphrase_hash'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // password_hash
            'browser_id_hash'    => ['type' => 'CHAR', 'constraint' => 64],      // 開催者の匿名ブラウザID
            'ip_hash'            => ['type' => 'CHAR', 'constraint' => 64],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'open'], // OogiriStatus
            'status_before_hide' => ['type' => 'VARCHAR', 'constraint' => 24, 'null' => true],   // 再公開時に戻す状態
            'fixable'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],    // 非表示が修正可能な違反か（U-13）
            'starts_at'          => ['type' => 'DATETIME'],
            'ends_at'            => ['type' => 'DATETIME'],
            'selection_deadline' => ['type' => 'DATETIME', 'null' => true],
            'decided_at'         => ['type' => 'DATETIME', 'null' => true],
            'pv'                 => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'answer_count'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'share_count'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'derived_count'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'report_count'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'parent_oogiri_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addKey('ends_at');
        $this->forge->addKey('selection_deadline');
        $this->forge->addKey('parent_oogiri_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('oogiris');
    }

    public function down(): void
    {
        $this->forge->dropTable('oogiris');
    }
}
