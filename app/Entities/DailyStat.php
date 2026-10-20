<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 日次集計（daily_stats テーブルの1行。S-06）
 *
 * @property int  $answerers     回答を完了したブラウザ数
 * @property int  $answers
 * @property int  $converted     回答者のうち、その後に開催したブラウザ数
 * @property Time $date
 * @property int  $derived       派生元つきの開催数
 * @property int  $hidden
 * @property int  $id
 * @property int  $oogiris
 * @property int  $pv
 * @property int  $reports
 * @property int  $share_answer
 * @property int  $share_recruit
 * @property int  $share_result
 */
final class DailyStat extends Entity
{
    protected $dates = ['date', 'created_at', 'updated_at'];
    protected $casts = [
        'id'           => 'int', 'pv' => 'int', 'oogiris' => 'int', 'answers' => 'int', 'share_recruit' => 'int', 'share_answer' => 'int',
        'share_result' => 'int', 'reports' => 'int', 'hidden' => 'int', 'answerers' => 'int', 'converted' => 'int', 'derived' => 'int',
    ];
}
