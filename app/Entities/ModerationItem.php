<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AiVerdict;
use App\Enums\AnswerStatus;
use App\Enums\ModerationFlag;
use App\Enums\OogiriStatus;
use App\Enums\ReportTarget;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 通報・モデレーション（A-05）の1件
 *
 * テーブルの1行ではなく、対象（お題／回答）ごとに reports・NGワード検知・異常投稿判定・AI判定をまとめたもの。
 * 工程4では ModerationService がこの形に組み立てる。
 *
 * @property string         $abnormal      異常投稿判定の説明。なければ「なし」
 * @property string         $ai_reason     AI判定の理由
 * @property string         $ai_verdict    AiVerdict の値
 * @property Time           $created_at    対象の投稿日時
 * @property list<string>   $flags         ModerationFlag の値（優先度順）
 * @property list<AdminLog> $history       対応履歴（新しい順）
 * @property int            $id            対象のID（target_type とあわせて一意）
 * @property string         $ng_detail     NGワード検知の説明。なければ「なし」
 * @property string         $odai          対象が回答のときの親のお題。お題自身なら空
 * @property int            $oogiri_id
 * @property int            $report_count  通報数（同一環境の重複を除いた数）
 * @property string         $report_detail 通報の内訳の説明。なければ「なし」
 * @property bool           $resolved      処理済みか
 * @property string         $target_status 対象の現在の状態（OogiriStatus または AnswerStatus の値）
 * @property string         $target_type   ReportTarget の値
 * @property string         $text          対象の本文（お題またはボケ）
 */
final class ModerationItem extends Entity
{
    protected $dates = ['created_at'];
    protected $casts = ['id' => 'int', 'oogiri_id' => 'int', 'report_count' => 'int', 'resolved' => 'bool'];

    public function target(): ReportTarget
    {
        return ReportTarget::from($this->attributes['target_type']);
    }

    public function aiVerdict(): AiVerdict
    {
        return AiVerdict::from($this->attributes['ai_verdict']);
    }

    /**
     * @return list<ModerationFlag>
     */
    public function flags(): array
    {
        return array_map(static fn (string $f) => ModerationFlag::from($f), $this->attributes['flags']);
    }

    /**
     * 対象の現在の状態の表示名（お題なら OogiriStatus、回答なら AnswerStatus）
     */
    public function targetStatusLabel(): string
    {
        return $this->target() === ReportTarget::Answer
            ? AnswerStatus::from($this->attributes['target_status'])->label()
            : OogiriStatus::from($this->attributes['target_status'])->label();
    }

    /**
     * 一覧の URL や dialog の id に使うキー（例：answer-90398）
     */
    public function key(): string
    {
        return $this->attributes['target_type'] . '-' . $this->attributes['id'];
    }

    /**
     * 表示用の ID（例：A-90398 ／ #12471）
     */
    public function displayId(): string
    {
        return $this->target() === ReportTarget::Answer ? 'A-' . $this->attributes['id'] : '#' . $this->attributes['id'];
    }
}
