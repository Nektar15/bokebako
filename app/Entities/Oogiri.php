<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\OogiriStatus;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 大喜利（oogiris テーブルの1行。SPEC 3.3）
 *
 * @property int       $answer_count
 * @property Time|null $decided_at
 * @property Time      $ends_at
 * @property bool      $fixable            非表示のとき、修正して再募集できるか（U-13）
 * @property int       $id
 * @property string    $odai
 * @property int|null  $parent_oogiri_id   派生元
 * @property int       $pv
 * @property Time|null $selection_deadline 結果選出の期限（ends_at + 72時間）
 * @property string    $slug               公開URL用（推測困難）
 * @property Time      $starts_at
 * @property string    $status             OogiriStatus の値
 */
final class Oogiri extends Entity
{
    protected $dates = ['starts_at', 'ends_at', 'selection_deadline', 'decided_at', 'created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'pv' => 'int', 'answer_count' => 'int', 'parent_oogiri_id' => '?int', 'fixable' => 'bool'];

    public function status(): OogiriStatus
    {
        return OogiriStatus::from($this->attributes['status']);
    }

    public function isOpen(): bool
    {
        return $this->status() === OogiriStatus::Open;
    }
}
