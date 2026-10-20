<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Restriction;

/**
 * 投稿元の制限（restrictions テーブル）の読み書き
 */
final class RestrictionModel extends BaseModel
{
    protected $table         = 'restrictions';
    protected $returnType    = Restriction::class;
    protected $allowedFields = ['browser_id_hash', 'ip_hash', 'until', 'reason', 'created_at'];
    protected $useTimestamps = false; // created_at だけを持つ（Service が入れる）
}
