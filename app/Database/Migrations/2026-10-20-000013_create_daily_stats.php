<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 日次集計（S-06。ダッシュボード A-02 はここだけを読む）
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateDailyStats extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'date'          => ['type' => 'DATE'],
            'pv'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'oogiris'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'answers'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'share_recruit' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'share_answer'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'share_result'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reports'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'hidden'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'answerers'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0], // 回答を完了したブラウザ数
            'converted'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0], // その後に開催したブラウザ数
            'derived'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0], // 派生元つきの開催数
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('date');
        $this->forge->createTable('daily_stats');
    }

    public function down(): void
    {
        $this->forge->dropTable('daily_stats');
    }
}
