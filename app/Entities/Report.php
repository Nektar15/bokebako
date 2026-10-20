<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\ReportTarget;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 通報（reports テーブルの1行。SPEC 3.3、S-04）
 *
 * @property string $browser_id_hash
 * @property Time   $created_at
 * @property int    $id
 * @property string $ip_hash
 * @property string $reason          通報理由（ReportReason の値）
 * @property bool   $resolved
 * @property int    $target_id
 * @property string $target_type     ReportTarget の値
 */
final class Report extends Entity
{
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'target_id' => 'int', 'resolved' => 'bool'];

    public function target(): ReportTarget
    {
        return ReportTarget::from($this->attributes['target_type']);
    }
}
