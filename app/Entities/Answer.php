<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AnswerStatus;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 回答（answers テーブルの1行。SPEC 3.3）
 *
 * @property string   $body
 * @property Time     $created_at
 * @property int      $id
 * @property bool     $is_favorite  開催者のお気に入り（非公開）
 * @property bool     $is_mine      表示中のブラウザが投稿したものか（Service が付ける）
 * @property int      $oogiri_id
 * @property string   $posted_label 表示用の投稿時刻（例：「5分前」。Service が付ける）
 * @property int|null $rank         1〜3位。未選出は null
 * @property string   $status       AnswerStatus の値
 */
final class Answer extends Entity
{
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'oogiri_id' => 'int', 'rank' => '?int', 'is_favorite' => 'bool', 'is_mine' => 'bool'];

    public function status(): AnswerStatus
    {
        return AnswerStatus::from($this->attributes['status']);
    }
}
