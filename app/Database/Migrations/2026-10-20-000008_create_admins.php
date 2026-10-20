<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 管理者アカウント（SPEC 3.3 admins。A-01 で使う。ログイン試行は admin_login_attempts）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateAdmins extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'login_id'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 64],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255], // bcrypt
            'role'          => ['type' => 'VARCHAR', 'constraint' => 8],   // AdminRole
            'totp_secret'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'failed_count'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'locked_until'  => ['type' => 'DATETIME', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('login_id');
        $this->forge->createTable('admins');
    }

    public function down(): void
    {
        $this->forge->dropTable('admins');
    }
}
