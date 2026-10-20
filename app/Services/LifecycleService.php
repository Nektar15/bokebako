<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Oogiri;
use App\Enums\OgpKind;
use App\Enums\OogiriStatus;
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
     * 更新は「今も募集中」を条件にする。読み込んだ後に確定・非表示されたものを古い状態で上書きしない（レビュー指摘 4）
     *
     * @return int 遷移した件数
     */
    public function closeEnded(?Time $now = null): int
    {
        $now ??= Time::now();
        $rows  = $this->oogiris->where('status', OogiriStatus::Open->value)->where('ends_at <=', $now->toDateTimeString())->findAll(500);
        $count = 0;

        foreach ($rows as $oogiri) {
            $changed = $this->transition($oogiri, OogiriStatus::Open, [
                'status'             => OogiriStatus::AwaitingSelection->value,
                'selection_deadline' => $this->selectionDeadline($oogiri)->toDateTimeString(),
            ]);
            if (! $changed) {
                continue;
            }
            service('pageCache')->purgeOogiri($oogiri->slug);
            service('adminLog')->record('oogiri', $oogiri->id, '募集終了 → 結果選択待ち', AdminLogService::ACTOR_AUTO);
            $count++;
        }

        return $count;
    }

    /**
     * S-02：選出期限を過ぎた「結果選択待ち」を「結果未選出」として確定し、U-12 のキャッシュを作り直す
     *
     * 更新は「今も結果選択待ち」を条件にする。同時に開催者が確定した結果を上書きしない（レビュー指摘 4）
     *
     * @return int 遷移した件数
     */
    public function expireUnselected(?Time $now = null): int
    {
        $now ??= Time::now();
        $rows  = $this->oogiris->where('status', OogiriStatus::AwaitingSelection->value)->where('selection_deadline <=', $now->toDateTimeString())->findAll(500);
        $count = 0;

        foreach ($rows as $oogiri) {
            if (! $this->transition($oogiri, OogiriStatus::AwaitingSelection, ['status' => OogiriStatus::NoResult->value, 'decided_at' => $now->toDateTimeString()])) {
                continue;
            }
            service('pageCache')->purgeOogiri($oogiri->slug);
            service('ogp')->generateResult($this->oogiris->find($oogiri->id), null);
            service('adminLog')->record('oogiri', $oogiri->id, '選出期限を過ぎたため結果未選出で確定', AdminLogService::ACTOR_AUTO);
            $count++;
        }

        return $count;
    }

    /**
     * 選出期限（S-01：ends_at ＋ 運用設定の選出時間）。再公開（A-03）でも同じ計算を使う
     */
    public function selectionDeadline(Oogiri $oogiri): Time
    {
        return $oogiri->ends_at->addHours($this->settings->int('select_hours'));
    }

    /**
     * 状態が $from のままのときだけ更新する。更新したら true
     *
     * @param array<string, string> $data
     */
    private function transition(Oogiri $oogiri, OogiriStatus $from, array $data): bool
    {
        $this->oogiris->where('id', $oogiri->id)->where('status', $from->value)->set($data)->update();

        return $this->oogiris->db->affectedRows() === 1;
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
                $count += $ogp->generateResult($oogiri, $ogp->firstRanked($oogiri)) ? 1 : 0;
            }
        }

        return $count;
    }
}
