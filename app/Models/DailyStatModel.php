<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\DailyStat;

/**
 * 日次集計（S-06）（daily_stats テーブル）の読み書き
 *
 * @method DailyStat|null  find(int|string|null $id = null)
 * @method list<DailyStat> findAll(int $limit = 0, int $offset = 0)
 * @method DailyStat|null  first()
 */
final class DailyStatModel extends BaseModel
{
    protected $table         = 'daily_stats';
    protected $returnType    = DailyStat::class;
    protected $allowedFields = ['date', 'pv', 'oogiris', 'answers', 'share_recruit', 'share_answer', 'share_result', 'reports', 'hidden', 'answerers', 'converted', 'derived'];
}
