<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AiJudgement;
use App\Entities\ModerationItem;
use App\Enums\AiVerdict;
use App\Enums\AnswerStatus;
use App\Enums\ModerationFlag;
use App\Enums\OogiriStatus;
use App\Enums\ReportReason;
use App\Enums\ReportTarget;
use App\Exceptions\NotFoundException;
use App\Libraries\OpenAiClient;
use App\Models\AiJudgementModel;
use App\Models\AnswerModel;
use App\Models\OogiriModel;
use App\Models\ReportModel;
use CodeIgniter\I18n\Time;

/**
 * 通報・モデレーション（A-05、S-04、S-05）
 *
 * 待ち行列は ai_judgements テーブル（NGワード「要確認」・通報・異常投稿のどれかで1行できる）。
 * 対象ごとに通報の内訳・NG検知・AI判定をまとめて ModerationItem にする。
 */
final class ModerationService
{
    /**
     * 絞り込みの選択肢（値 => 表示名）。'all' は未処理すべて、'resolved' は処理済み
     */
    public const FILTERS = [
        'all'       => '未処理すべて',
        'surge'     => '通報急増',
        'ng_word'   => 'NGワード検知',
        'ai_review' => 'AI「要確認」以上',
        'abnormal'  => '異常投稿',
        'resolved'  => '処理済み',
    ];

    /**
     * 投稿元を制限する期間の選択肢（値 => 表示名）
     */
    public const RESTRICT_HOURS = [
        '1'   => '1時間',
        '24'  => '24時間',
        '168' => '7日間',
    ];

    public function __construct(
        private readonly AiJudgementModel $judgements,
        private readonly ReportModel $reports,
        private readonly OogiriModel $oogiris,
        private readonly AnswerModel $answers,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * 対象を待ち行列に入れる（なければ作る。あれば検知の内容を足す）。S-05 の判定対象もここで決まる
     *
     * @param string $trigger ng_review / reported / abnormal
     */
    public function flag(ReportTarget $target, int $id, string $trigger, ?string $ngDetail = null, ?string $abnormal = null): void
    {
        $row     = $this->judgements->where('target_type', $target->value)->where('target_id', $id)->first();
        $targets = $this->settings->list('ai_targets');
        $queue   = $this->settings->bool('ai_enabled') && in_array($trigger, $targets, true) ? 1 : 0;

        if ($row === null) {
            $this->judgements->insert([
                'target_type' => $target->value, 'target_id' => $id, 'trigger' => $trigger,
                'ng_detail'   => $ngDetail, 'abnormal' => $abnormal, 'verdict' => AiVerdict::None->value, 'queued' => $queue, 'resolved' => 0,
            ]);

            return;
        }
        $update = ['resolved' => 0];

        if ($ngDetail !== null) {
            $update['ng_detail'] = $ngDetail;
        }
        if ($abnormal !== null) {
            $update['abnormal'] = $abnormal;
        }
        if ($row->verdict() === AiVerdict::None && $queue === 1) {
            $update['queued'] = 1;
        }
        $this->judgements->update($row->id, $update);
    }

    /**
     * 待ち行列（優先度の高い順：通報急増 → 通報件数 → NG検知 → 異常投稿 → AI判定）
     *
     * @return list<ModerationItem>
     */
    public function queue(string $filter, int $limit = 50): array
    {
        $rows  = $this->judgements->where('resolved', $filter === 'resolved' ? 1 : 0)->orderBy('updated_at', 'DESC')->findAll(200);
        $items = [];

        foreach ($rows as $row) {
            $item = $this->build($row);

            if ($item === null) {
                continue;
            }
            $keep = match ($filter) {
                'all', 'resolved' => true,
                'ai_review'       => in_array($item->aiVerdict(), [AiVerdict::Review, AiVerdict::Hide], true),
                default           => in_array(ModerationFlag::from($filter), $item->flags(), true),
            };
            if ($keep) {
                $items[] = $item;
            }
        }
        usort($items, static fn (ModerationItem $a, ModerationItem $b) => self::priority($a) <=> self::priority($b) ?: $b->report_count <=> $a->report_count);

        return array_slice($items, 0, $limit);
    }

    /**
     * 絞り込みごとの件数
     *
     * @return array<string, int> FILTERS のキー => 件数
     */
    public function counts(): array
    {
        $open   = $this->queue('all', 200);
        $counts = ['all' => count($open), 'surge' => 0, 'ng_word' => 0, 'ai_review' => 0, 'abnormal' => 0, 'resolved' => $this->judgements->where('resolved', 1)->countAllResults()];

        foreach ($open as $item) {
            $flags = $item->flags();
            $counts['surge'] += in_array(ModerationFlag::Surge, $flags, true) ? 1 : 0;
            $counts['ng_word'] += in_array(ModerationFlag::NgWord, $flags, true) ? 1 : 0;
            $counts['abnormal'] += in_array(ModerationFlag::Abnormal, $flags, true) ? 1 : 0;
            $counts['ai_review'] += in_array($item->aiVerdict(), [AiVerdict::Review, AiVerdict::Hide], true) ? 1 : 0;
        }

        return $counts;
    }

    /**
     * 待ち行列の1件（キーは ModerationItem::key()）
     *
     * @throws NotFoundException
     */
    public function find(string $key): ModerationItem
    {
        [$type, $id] = array_pad(explode('-', $key, 2), 2, '');
        $target      = ReportTarget::tryFrom($type);
        $row         = $target === null ? null : $this->judgements->where('target_type', $target->value)->where('target_id', (int) $id)->first();
        $item        = $row === null ? null : $this->build($row);

        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /**
     * 問題なし（通報を処理済みにする。非表示中でも再公開はしない：別操作）
     */
    public function resolve(string $key): void
    {
        $item = $this->find($key);
        $this->markResolved($item);
        service('adminLog')->record($item->target_type, $item->id, '問題なし（通報を処理済みに）');
    }

    /**
     * 非表示にする
     */
    public function hide(string $key): void
    {
        $item = $this->find($key);
        $item->target() === ReportTarget::Answer ? service('answerAdmin')->hide($item->id) : service('oogiriAdmin')->hide($item->id);
        $this->markResolved($item);
    }

    /**
     * 削除する
     */
    public function delete(string $key): void
    {
        $item = $this->find($key);
        $item->target() === ReportTarget::Answer ? service('answerAdmin')->delete($item->id) : service('oogiriAdmin')->delete($item->id);
        $this->markResolved($item);
    }

    /**
     * 投稿元を一定期間制限する
     *
     * @param int $hours RESTRICT_HOURS のキー
     */
    public function restrict(string $key, int $hours): void
    {
        $item   = $this->find($key);
        $source = $item->target() === ReportTarget::Answer ? $this->answers->find($item->id) : $this->oogiris->find($item->id);

        if ($source === null) {
            throw new NotFoundException();
        }
        service('rateLimit')->restrict($hours * 3600, '管理者による制限（' . $item->displayId() . '）', $source->browser_id_hash, $source->ip_hash);
        service('adminLog')->record($item->target_type, $item->id, '投稿元を' . self::RESTRICT_HOURS[(string) $hours] . '制限');
    }

    /**
     * 通報の集計（S-04、cron）：条件に達した対象を一時非表示にして管理者に通知する
     *
     * 同一環境（ブラウザID）からの連続通報は1件として数える。
     *
     * @return int 非表示にした件数
     */
    public function processReports(): int
    {
        $window = Time::now()->subMinutes($this->settings->int('report_window_min'))->toDateTimeString();
        $rows   = $this->reports->builder()
            ->select('target_type, target_id, COUNT(DISTINCT browser_id_hash) AS environments')
            ->where('resolved', 0)->where('created_at >=', $window)
            ->groupBy('target_type, target_id')
            ->having('environments >=', $this->settings->int('report_count'))
            ->get()->getResultArray();
        $hidden = 0;

        foreach ($rows as $row) {
            $target = ReportTarget::from($row['target_type']);
            $id     = (int) $row['target_id'];
            $entity = $target === ReportTarget::Answer ? $this->answers->find($id) : $this->oogiris->find($id);

            if ($entity === null) {
                continue;
            }
            $isVisible = $target === ReportTarget::Answer ? $entity->status() === AnswerStatus::Visible : $entity->status() === OogiriStatus::Open;

            if (! $isVisible) {
                continue;
            }
            $target === ReportTarget::Answer
                ? service('answerAdmin')->hide($id, AdminLogService::ACTOR_AUTO, '通報が条件に達したため一時非表示')
                : service('oogiriAdmin')->hide($id, AdminLogService::ACTOR_AUTO, '通報が条件に達したため一時非表示');
            $this->flag($target, $id, 'reported');
            $hidden++;
        }
        if ($hidden > 0) {
            service('mail')->notifyAdmin("通報により {$hidden} 件を一時非表示にしました", "管理画面の「通報・モデレーション」で確認してください。\n" . site_url(trim((string) env('app.adminPath', 'admin'), '/') . '/moderation'));
            service('adminLog')->record('answer', null, "通報の集計：{$hidden} 件を一時非表示にし、管理者へメール通知", AdminLogService::ACTOR_AUTO);
        }

        return $hidden;
    }

    /**
     * AI補助判定のキューを処理する（S-05、cron）。月間上限に達したら残りは未判定のまま
     *
     * @return int 判定した件数
     */
    public function judgeQueue(int $batch = 20): int
    {
        if (! $this->settings->bool('ai_enabled')) {
            return 0;
        }
        $limit = $this->settings->int('ai_monthly_limit');
        $used  = $this->settings->aiMonthlyUsed();
        $rows  = $this->judgements->where('queued', 1)->orderBy('created_at', 'ASC')->findAll($batch);
        $done  = 0;
        $ai    = new OpenAiClient();

        foreach ($rows as $row) {
            if ($used + $done >= $limit) {
                break;
            }
            $texts = $this->texts($row);

            if ($texts === null) {
                $this->judgements->update($row->id, ['queued' => 0, 'resolved' => 1]);

                continue;
            }
            $result = $ai->judge($texts['odai'], $texts['text']);
            $this->judgements->update($row->id, ['queued' => $result['verdict'] === AiVerdict::None ? 1 : 0, 'verdict' => $result['verdict']->value, 'reason' => $result['reason']]);

            if ($result['verdict'] === AiVerdict::None) {
                break; // API が応答しないときは次回に回す
            }
            $done++;

            if ($result['verdict'] === AiVerdict::Hide && $this->settings->bool('ai_auto_hide')) {
                $row->target() === ReportTarget::Answer
                    ? service('answerAdmin')->hide($row->target_id, AdminLogService::ACTOR_AUTO, 'AI判定「非表示推奨」により自動で非表示')
                    : service('oogiriAdmin')->hide($row->target_id, AdminLogService::ACTOR_AUTO, 'AI判定「非表示推奨」により自動で非表示');
            }
        }

        return $done;
    }

    private function markResolved(ModerationItem $item): void
    {
        $this->judgements->where('target_type', $item->target_type)->where('target_id', $item->id)->set(['resolved' => 1, 'queued' => 0])->update();
        $this->reports->where('target_type', $item->target_type)->where('target_id', $item->id)->set(['resolved' => 1])->update();
    }

    /**
     * ai_judgements の1行から表示用の1件を組み立てる。対象が消えていれば null
     */
    private function build(AiJudgement $row): ?ModerationItem
    {
        $texts = $this->texts($row);

        if ($texts === null) {
            return null;
        }
        $reports = $this->reports->where('target_type', $row->target_type)->where('target_id', $row->target_id)->where('resolved', 0)->findAll();
        $byEnv   = [];
        $reasons = [];
        $recent  = 0;
        $hourAgo = Time::now()->subHours(1);

        foreach ($reports as $report) {
            $byEnv[$report->browser_id_hash] = true;
            $label                           = (ReportReason::tryFrom($report->reason) ?? ReportReason::Other)->label();
            $reasons[$label]                 = ($reasons[$label] ?? 0) + 1;
            $recent += $report->created_at->isAfter($hourAgo) ? 1 : 0;
        }
        $count  = count($byEnv);
        $detail = $count === 0 ? 'なし' : "{$count}件" . (count($reports) > $count ? '（同一環境の重複' . (count($reports) - $count) . '件を除外）' : '') . ' ／ ' . implode('・', array_map(static fn ($k, $v) => "{$k} {$v}", array_keys($reasons), $reasons));
        $flags  = [];

        if ($recent >= $this->settings->int('surge_count')) {
            $flags[] = ModerationFlag::Surge->value;
        }
        if ($count > 0) {
            $flags[] = ModerationFlag::Reported->value;
        }
        if ($row->ng_detail !== null && $row->ng_detail !== '') {
            $flags[] = ModerationFlag::NgWord->value;
        }
        if ($row->abnormal !== null && $row->abnormal !== '') {
            $flags[] = ModerationFlag::Abnormal->value;
        }
        if ($row->verdict() === AiVerdict::Review) {
            $flags[] = ModerationFlag::AiReview->value;
        }
        if ($row->verdict() === AiVerdict::Hide) {
            $flags[] = ModerationFlag::AiHide->value;
        }

        return new ModerationItem([
            'target_type'   => $row->target_type,
            'id'            => $row->target_id,
            'oogiri_id'     => $texts['oogiri_id'],
            'text'          => $texts['text'],
            'odai'          => $row->target() === ReportTarget::Answer ? $texts['odai'] : '',
            'flags'         => $flags,
            'created_at'    => $texts['created_at'],
            'report_count'  => $count,
            'target_status' => $texts['status'],
            'report_detail' => $detail,
            'ng_detail'     => $row->ng_detail ?: 'なし',
            'abnormal'      => $row->abnormal ?: 'なし',
            'ai_verdict'    => $row->verdict,
            'ai_reason'     => $row->reason ?? ($row->queued ? 'まだ判定していません（次回のバッチで判定します）' : 'AI判定の対象外です。'),
            'resolved'      => $row->resolved,
            'history'       => service('adminLog')->listFor($row->target_type, $row->target_id, 10),
        ]);
    }

    /**
     * 対象の本文・お題・状態。消えていれば null
     *
     * @return array{text: string, odai: string, oogiri_id: int, status: string, created_at: Time}|null
     */
    private function texts(AiJudgement $row): ?array
    {
        if ($row->target() === ReportTarget::Answer) {
            $answer = $this->answers->find($row->target_id);
            $oogiri = $answer !== null ? $this->oogiris->find($answer->oogiri_id) : null;

            return $answer === null || $oogiri === null ? null
                : ['text' => $answer->body, 'odai' => $oogiri->odai, 'oogiri_id' => $oogiri->id, 'status' => $answer->status, 'created_at' => $answer->created_at];
        }
        $oogiri = $this->oogiris->find($row->target_id);

        return $oogiri === null ? null
            : ['text' => $oogiri->odai, 'odai' => $oogiri->odai, 'oogiri_id' => $oogiri->id, 'status' => $oogiri->status, 'created_at' => $oogiri->created_at];
    }

    private static function priority(ModerationItem $item): int
    {
        $order = array_flip(array_map(static fn (ModerationFlag $f) => $f->value, ModerationFlag::cases()));
        $best  = PHP_INT_MAX;

        foreach ($item->flags() as $flag) {
            $best = min($best, $order[$flag->value]);
        }

        return $best;
    }
}
