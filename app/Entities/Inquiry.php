<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * お問い合わせ（inquiries テーブルの1行。SPEC 3.3）
 *
 * @property string         $body
 * @property Time           $created_at 受付日時
 * @property string         $email
 * @property int            $id
 * @property list<AdminLog> $memos      対応メモ（新しい順。actor＝記録した管理者、what＝メモ）
 * @property string         $status     InquiryStatus の値
 * @property string         $type       InquiryType の値
 * @property string         $url        対象URL（なければ空）
 */
final class Inquiry extends Entity
{
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = ['id' => 'int'];

    public function type(): InquiryType
    {
        return InquiryType::from($this->attributes['type']);
    }

    public function status(): InquiryStatus
    {
        return InquiryStatus::from($this->attributes['status']);
    }
}
