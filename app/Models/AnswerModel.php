<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Answer;

/**
 * 回答（answers テーブル）の読み書き
 */
final class AnswerModel extends BaseModel
{
    protected $table         = 'answers';
    protected $returnType    = Answer::class;
    protected $allowedFields = ['oogiri_id', 'body', 'status', 'rank', 'is_favorite', 'browser_id_hash', 'ip_hash', 'report_count'];
}
