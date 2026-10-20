<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * 全 Model の親。DB の読み書きだけを置く（CODING_RULES 3章）。
 *
 * 日時は Asia/Tokyo で統一する（CODING_RULES 1章）。MySQL/MariaDB では接続のセッションにも +09:00 を設定する。
 */
abstract class BaseModel extends Model
{
    protected $useTimestamps             = true;
    protected $dateFormat                = 'datetime';
    private static bool $timezoneApplied = false;

    protected function initialize(): void
    {
        if (self::$timezoneApplied || $this->db->DBDriver !== 'MySQLi') {
            return;
        }
        $this->db->query("SET time_zone = '+09:00'");
        self::$timezoneApplied = true;
    }

    /**
     * カウンタ列を SET x = x + n の形で更新する（読んでから書き戻さない：CODING_RULES 6章）
     */
    public function increment(int $id, string $column, int $by = 1): void
    {
        $this->builder()->where('id', $id)->set($column, "{$column} + {$by}", false)->update();
    }

    /**
     * カウンタ列を 0 未満にならないように減らす
     */
    public function decrement(int $id, string $column, int $by = 1): void
    {
        $this->builder()->where('id', $id)->set($column, "GREATEST({$column} - {$by}, 0)", false)->update();
    }
}
