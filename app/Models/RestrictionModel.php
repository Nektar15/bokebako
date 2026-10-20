<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Restriction;

/**
 * 投稿元の制限（restrictions テーブル）の読み書き
 *
 * @method Restriction|null  find(int|string|null $id = null)
 * @method list<Restriction> findAll(int $limit = 0, int $offset = 0)
 * @method Restriction|null  first()
 */
final class RestrictionModel extends BaseModel
{
    protected $table         = 'restrictions';
    protected $returnType    = Restriction::class;
    protected $allowedFields = ['browser_id_hash', 'ip_hash', 'until', 'reason', 'created_at'];
    protected $useTimestamps = false; // created_at だけを持つ（Service が入れる）
}
