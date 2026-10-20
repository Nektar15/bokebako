<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\AdminLog;

/**
 * 操作履歴（admin_logs テーブル）の読み書き
 *
 * @method AdminLog|null  find(int|string|null $id = null)
 * @method list<AdminLog> findAll(int $limit = 0, int $offset = 0)
 * @method AdminLog|null  first()
 */
final class AdminLogModel extends BaseModel
{
    protected $table         = 'admin_logs';
    protected $returnType    = AdminLog::class;
    protected $allowedFields = ['actor', 'admin_id', 'target_type', 'target_id', 'what', 'created_at'];
    protected $useTimestamps = false; // created_at だけを持つ（Service が入れる）
}
