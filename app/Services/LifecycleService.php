<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OgpKind;
use App\Enums\OogiriStatus;
use App\Models\AnswerModel;
use App\Models\OogiriModel;
use CodeIgniter\I18n\Time;

/**
 * 大喜利の状態遷移の自動処理（S-01 24時間終了、S-02 72時間結果待ち）。cron（1分間隔）から Command が呼ぶ
 */
final class LifecycleService
{
    public function __construct(
        private readonly OogiriModel $oogiris,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * S-01：ends_at を過ぎた「募集中」を「結果選択待ち」にし、選出期限（ends_at ＋ 選出時間）を設定する
     *
     * @return int 遷移した件数
     */
    public function closeEnded(?Time $now = null): int
    {
        $now ??= Time::now();
        $hours = $this->settings->int('select_hours');
        $rows  = $this->oogiris->where('status', OogiriStatus::Open->value)->where('ends_at <=', $now->toDateTimeString())->findAll(500);

        foreach ($rows as $oogiri) {
            $this->oogiris->update($oogiri->id, [
                'status'             => OogiriStatus::AwaitingSelection->value,
                'selection_deadline' => $oogiri->ends_at->addHours($hours)->toDateTimeString(),
            ]);
            service('pageCache')->purgeOogiri($oogiri->slug);
            service('adminLog')->record('oogiri', $oogiri->id, '募集終了 → 結果選択待ち', AdminLogService::ACTOR_AUTO);
        }

        return count($rows);
    }

    /**
     * S-02：選出期限を過ぎた「結果選択待ち」を「結果未選出」として確定し、U-12 のキャッシュを作り直す
     *
     * @return int 遷移した件数
     */
    public function expireUnselected(?Time $now = null): int
    {
        $now ??= Time::now();
        $rows = $this->oogiris->where('status', OogiriStatus::AwaitingSelection->value)->where('selection_deadline <=', $now->toDateTimeString())->findAll(500);

        foreach ($rows as $oogiri) {
            $this->oogiris->update($oogiri->id, ['status' => OogiriStatus::NoResult->value, 'decided_at' => $now->toDateTimeString()]);
            service('pageCache')->purgeOogiri($oogiri->slug);
            service('ogp')->generateResult($this->oogiris->find($oogiri->id), null);
            service('adminLog')->record('oogiri', $oogiri->id, '選出期限を過ぎたため結果未選出で確定', AdminLogService::ACTOR_AUTO);
        }

        return count($rows);
    }

    /**
     * OGP 画像が欠けている募集中・確定済みの大喜利の画像を作る（S-07 の取りこぼし対策。ogp:generate）
     *
     * @return int 生成した件数
     */
    public function generateMissingOgp(int $limit = 50): int
    {
        $ogp   = service('ogp');
        $count = 0;
        $rows  = $this->oogiris->whereIn('status', [OogiriStatus::Open->value, OogiriStatus::AwaitingSelection->value, OogiriStatus::Decided->value, OogiriStatus::NoResult->value])
            ->orderBy('id', 'DESC')->findAll($limit);

        foreach ($rows as $oogiri) {
            if (! $ogp->exists(OgpKind::Recruit, $oogiri->slug) && $ogp->generateRecruit($oogiri)) {
                $count++;
            }
            $decided = in_array($oogiri->status(), [OogiriStatus::Decided, OogiriStatus::NoResult], true);

            if ($decided && ! $ogp->exists(OgpKind::Result, $oogiri->slug)) {
                $first = (new AnswerModel())->where('oogiri_id', $oogiri->id)->where('rank', 1)->first();
                $count += $ogp->generateResult($oogiri, $first) ? 1 : 0;
            }
        }

        return $count;
    }
}
