<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AiVerdict;
use App\Enums\ReportTarget;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * AI補助判定のキューと結果（ai_judgements テーブルの1行。S-05）。NGワード検知・異常投稿判定の記録も兼ねる
 *
 * @property string|null $abnormal    異常投稿判定の説明
 * @property Time        $created_at
 * @property int         $id
 * @property string|null $ng_detail   NGワード検知の説明
 * @property bool        $queued      これから API に送るか
 * @property string|null $reason      AI判定の理由
 * @property bool        $resolved    管理者が処理済みにしたか
 * @property int         $target_id
 * @property string      $target_type ReportTarget の値
 * @property string      $trigger     判定の起点：ng_review / reported / abnormal
 * @property string      $verdict     AiVerdict の値
 */
final class AiJudgement extends Entity
{
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'target_id' => 'int', 'queued' => 'bool', 'resolved' => 'bool'];

    public function target(): ReportTarget
    {
        return ReportTarget::from($this->attributes['target_type']);
    }

    public function verdict(): AiVerdict
    {
        return AiVerdict::from($this->attributes['verdict']);
    }
}
