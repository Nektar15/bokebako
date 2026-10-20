<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Answer;

/**
 * 回答（answers テーブル）の読み書き
 *
 * @method Answer|null  find(int|string|null $id = null)
 * @method list<Answer> findAll(int $limit = 0, int $offset = 0)
 * @method Answer|null  first()
 */
final class AnswerModel extends BaseModel
{
    protected $table         = 'answers';
    protected $returnType    = Answer::class;
    protected $allowedFields = ['oogiri_id', 'body', 'status', 'rank', 'is_favorite', 'browser_id_hash', 'ip_hash', 'report_count'];
}
