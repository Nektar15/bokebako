<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Ad;

/**
 * 広告（ads テーブル）の読み書き
 *
 * @method Ad|null  find(int|string|null $id = null)
 * @method list<Ad> findAll(int $limit = 0, int $offset = 0)
 * @method Ad|null  first()
 */
final class AdModel extends BaseModel
{
    protected $table         = 'ads';
    protected $returnType    = Ad::class;
    protected $allowedFields = ['name', 'sponsor', 'image', 'url', 'slot', 'status', 'starts_at', 'ends_at', 'impressions', 'clicks'];
}
