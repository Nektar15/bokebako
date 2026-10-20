<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\NgWord;

/**
 * NGワード（ng_words テーブル）の読み書き
 */
final class NgWordModel extends BaseModel
{
    protected $table         = 'ng_words';
    protected $returnType    = NgWord::class;
    protected $allowedFields = ['word', 'match_type', 'category', 'action', 'enabled'];
}
