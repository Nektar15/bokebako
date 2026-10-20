<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\AiJudgement;

/**
 * AI補助判定（S-05）とモデレーションの検知記録（ai_judgements テーブル）の読み書き
 */
final class AiJudgementModel extends BaseModel
{
    protected $table         = 'ai_judgements';
    protected $returnType    = AiJudgement::class;
    protected $allowedFields = ['target_type', 'target_id', 'trigger', 'ng_detail', 'abnormal', 'verdict', 'reason', 'queued', 'resolved'];
}
