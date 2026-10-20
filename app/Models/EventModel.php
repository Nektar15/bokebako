<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Event;

/**
 * 計測イベント（生ログ）（events テーブル）の読み書き
 */
final class EventModel extends BaseModel
{
    protected $table         = 'events';
    protected $returnType    = Event::class;
    protected $allowedFields = ['name', 'oogiri_id', 'answer_id', 'parent_oogiri_id', 'browser_id_hash', 'referer', 'utm', 'created_at'];
    protected $useTimestamps = false; // created_at だけを持つ（Service が入れる）
}
