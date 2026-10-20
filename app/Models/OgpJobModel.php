<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\OgpJob;

/**
 * OGP 生成記録（ogp_jobs テーブル）の読み書き
 */
final class OgpJobModel extends BaseModel
{
    protected $table         = 'ogp_jobs';
    protected $returnType    = OgpJob::class;
    protected $allowedFields = ['kind', 'target_id', 'target', 'actor', 'succeeded', 'error', 'created_at'];
    protected $useTimestamps = false; // created_at だけを持つ（Service が入れる）
}
