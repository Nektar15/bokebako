<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * AI補助判定のキューと結果（S-05）。NGワード検知・異常投稿判定の記録もここに持つ
 *
 * 共通カラム・インデックス・ハッシュ保存は CODING_RULES 6章。文字コードは utf8mb4 / utf8mb4_general_ci（Database.php）。
 */
class CreateAiJudgements extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'target_type' => ['type' => 'VARCHAR', 'constraint' => 8],
            'target_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'trigger'     => ['type' => 'VARCHAR', 'constraint' => 16],  // ng_review / reported / abnormal
            'ng_detail'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'abnormal'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'verdict'     => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'none'], // AiVerdict
            'reason'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'queued'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],   // 1 = これから API に送る
            'resolved'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],   // 管理者が処理済みにしたか
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['target_type', 'target_id']);
        $this->forge->addKey(['queued', 'created_at']);
        $this->forge->addKey('resolved');
        $this->forge->createTable('ai_judgements');
    }

    public function down(): void
    {
        $this->forge->dropTable('ai_judgements');
    }
}
