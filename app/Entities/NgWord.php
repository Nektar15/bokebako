<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\NgWordAction;
use App\Enums\NgWordMatchType;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * NGワード（ng_words テーブルの1行。SPEC 3.3）
 *
 * @property string $action     NgWordAction の値
 * @property string $category   カテゴリー（表示名そのまま）
 * @property bool   $enabled
 * @property int    $id
 * @property string $match_type NgWordMatchType の値
 * @property Time   $updated_at
 * @property string $word
 */
final class NgWord extends Entity
{
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'enabled' => 'bool'];

    public function matchType(): NgWordMatchType
    {
        return NgWordMatchType::from($this->attributes['match_type']);
    }

    public function action(): NgWordAction
    {
        return NgWordAction::from($this->attributes['action']);
    }
}
