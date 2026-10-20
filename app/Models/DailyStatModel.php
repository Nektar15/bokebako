<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\DailyStat;

/**
 * 日次集計（S-06）（daily_stats テーブル）の読み書き
 */
final class DailyStatModel extends BaseModel
{
    protected $table         = 'daily_stats';
    protected $returnType    = DailyStat::class;
    protected $allowedFields = ['date', 'pv', 'oogiris', 'answers', 'share_recruit', 'share_answer', 'share_result', 'reports', 'hidden', 'answerers', 'converted', 'derived'];
}
