<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Inquiry;

/**
 * お問い合わせ（inquiries テーブル）の読み書き
 *
 * @method Inquiry|null  find(int|string|null $id = null)
 * @method list<Inquiry> findAll(int $limit = 0, int $offset = 0)
 * @method Inquiry|null  first()
 */
final class InquiryModel extends BaseModel
{
    protected $table         = 'inquiries';
    protected $returnType    = Inquiry::class;
    protected $allowedFields = ['type', 'email', 'url', 'body', 'status'];
}
