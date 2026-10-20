<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 投稿元の制限（restrictions テーブルの1行。S-03、A-05）
 *
 * @property string|null $browser_id_hash
 * @property Time        $created_at
 * @property int         $id
 * @property string|null $ip_hash
 * @property string      $reason
 * @property Time        $until
 */
final class Restriction extends Entity
{
    protected $dates = ['until', 'created_at'];
    protected $casts = ['id' => 'int'];
}
