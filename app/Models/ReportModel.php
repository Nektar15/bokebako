<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Report;

/**
 * 通報（reports テーブル）の読み書き
 */
final class ReportModel extends BaseModel
{
    protected $table         = 'reports';
    protected $returnType    = Report::class;
    protected $allowedFields = ['target_type', 'target_id', 'reason', 'browser_id_hash', 'ip_hash', 'resolved'];
}
