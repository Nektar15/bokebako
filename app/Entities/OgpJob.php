<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\OgpKind;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * OGP 画像の生成記録の1件（A-08「最近の生成」）
 *
 * ogp_jobs テーブルの1行（OgpService が生成のたびに積む）。
 *
 * @property string $actor      実行した人（管理者名、または「自動」）
 * @property Time   $created_at
 * @property int    $id
 * @property string $kind       OgpKind の値
 * @property bool   $succeeded
 * @property string $target     対象の表示（例：「#12480 こんなコンビニ店員は嫌だ」「A-90412」）
 * @property int    $target_id  再試行に使う対象のID
 */
final class OgpJob extends Entity
{
    protected $dates = ['created_at'];
    protected $casts = ['id' => 'int', 'target_id' => 'int', 'succeeded' => 'bool'];

    public function kind(): OgpKind
    {
        return OgpKind::from($this->attributes['kind']);
    }
}
