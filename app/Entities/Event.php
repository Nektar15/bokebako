<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 計測イベントの生ログ（events テーブルの1行。S-06）
 *
 * @property int|null    $answer_id
 * @property string|null $browser_id_hash
 * @property Time        $created_at
 * @property int         $id
 * @property string      $name             EventName の値
 * @property int|null    $oogiri_id
 * @property int|null    $parent_oogiri_id
 * @property string|null $referer
 * @property string|null $utm
 */
final class Event extends Entity
{
    protected $dates = ['created_at'];
    protected $casts = ['id' => 'int', 'oogiri_id' => '?int', 'answer_id' => '?int', 'parent_oogiri_id' => '?int'];
}
