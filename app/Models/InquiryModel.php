<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Inquiry;

/**
 * お問い合わせ（inquiries テーブル）の読み書き
 */
final class InquiryModel extends BaseModel
{
    protected $table         = 'inquiries';
    protected $returnType    = Inquiry::class;
    protected $allowedFields = ['type', 'email', 'url', 'body', 'status'];
}
