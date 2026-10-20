<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Oogiri;
use App\Enums\AnswerStatus;
use App\Enums\ReportReason;
use App\Enums\ReportTarget;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\AnswerModel;
use App\Models\OogiriModel;
use App\Models\ReportModel;
use CodeIgniter\I18n\Time;

/**
 * 通報の受付（POST /api/v1/reports、S-04）
 *
 * 同一環境からの同じ対象への通報は1件として数える（2回目以降は受け付けたことにして保存しない）。
 * 条件に達したときの一時非表示は cron（reports:process）が行う。
 */
final class ReportService
{
    public function __construct(
        private readonly ReportModel $reports,
        private readonly OogiriModel $oogiris,
        private readonly AnswerModel $answers,
        private readonly IdentityService $identity,
        private readonly RateLimitService $rateLimit,
        private readonly SettingService $settings,
        private readonly ModerationService $moderation,
    ) {
    }

    /**
     * @param array{type?: string, id?: int|string, reason?: string} $input type は odai / answer（画面の値）
     *
     * @throws AppException        レート制限
     * @throws NotFoundException   対象がない
     * @throws ValidationException 理由がない
     */
    public function create(Oogiri $oogiri, array $input): void
    {
        $this->rateLimit->assertAllowed(RateLimitService::ACTION_REPORT);
        $reason = ReportReason::tryFrom((string) ($input['reason'] ?? ''));
        $target = ($input['type'] ?? '') === 'answer' ? ReportTarget::Answer : ReportTarget::Oogiri;
        $id     = $target === ReportTarget::Answer ? (int) ($input['id'] ?? 0) : $oogiri->id;

        if ($reason === null) {
            throw new ValidationException(['reason' => '通報の理由を選んでください']);
        }
        if ($target === ReportTarget::Answer) {
            $answer = $this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value)->find($id);
            if ($answer === null) {
                throw new NotFoundException();
            }
        }
        $browser = $this->identity->browserIdHash();
        $already = $browser !== '' && $this->reports->where('target_type', $target->value)->where('target_id', $id)->where('browser_id_hash', $browser)->countAllResults() > 0;

        if ($already) {
            return;
        }
        // 通報の保存と通報数の加算は一緒に成功・失敗させる
        $this->reports->transaction(function () use ($target, $id, $reason, $browser, $oogiri): void {
            $this->reports->insert([
                'target_type'     => $target->value,
                'target_id'       => $id,
                'reason'          => $reason->value,
                'browser_id_hash' => $browser,
                'ip_hash'         => $this->identity->ipHash(),
                'resolved'        => 0,
            ]);
            if ($target === ReportTarget::Answer) {
                $this->answers->increment($id, 'report_count');
            }
            $this->oogiris->increment($oogiri->id, 'report_count');
        });
        $this->moderation->flag($target, $id, 'reported');

        // 通報のたびに管理者へ通知。急増時（1時間に surge_count 件超）は cron のまとめ通知に任せる（FEATURES 0-8）
        $recent = $this->reports->where('created_at >=', Time::now()->subHours(1)->toDateTimeString())->countAllResults();

        if ($recent <= $this->settings->int('surge_count')) {
            service('mail')->notifyAdmin('通報を受け付けました（' . $reason->label() . '）', "対象：{$target->label()} {$id}\n管理画面の「通報・モデレーション」で確認してください。");
        }
    }
}
