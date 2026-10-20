<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Report;

/**
 * 通報（reports テーブル）の読み書き
 *
 * @method Report|null  find(int|string|null $id = null)
 * @method list<Report> findAll(int $limit = 0, int $offset = 0)
 * @method Report|null  first()
 */
final class ReportModel extends BaseModel
{
    protected $table         = 'reports';
    protected $returnType    = Report::class;
    protected $allowedFields = ['target_type', 'target_id', 'reason', 'browser_id_hash', 'ip_hash', 'resolved'];
}
