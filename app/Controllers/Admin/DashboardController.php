<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Enums\StatsPeriod;

/**
 * A-02 管理ダッシュボード
 */
final class DashboardController extends AdminController
{
    /**
     * A-02（?period= で集計期間を切り替える）
     */
    public function index(): string
    {
        $period  = StatsPeriod::tryFrom((string) $this->request->getGet('period')) ?? StatsPeriod::Today;
        $stats   = service('stats');
        $display = service('display');
        $todos   = $stats->todos();
        $periods = [];

        foreach (StatsPeriod::cases() as $case) {
            $periods[] = ['label' => $case->label(), 'url' => admin_url('?period=' . $case->value), 'active' => $case === $period];
        }

        return $this->render('admin/a02_dashboard', [
            'screenId'   => 'A-02',
            'title'      => 'ダッシュボード',
            'periods'    => $periods,
            'rangeLabel' => $stats->rangeLabel($period),
            'kpi'        => $stats->kpi($period),
            'tiles'      => $stats->tiles($period),
            'chart'      => $stats->dailyChart(),
            'todos'      => [
                ['label' => '通報・モデレーション待ち', 'count' => $display->number($todos['moderation']), 'url' => admin_url('moderation')],
                ['label' => 'NGワード「要確認」', 'count' => $display->number($todos['ngReview']), 'url' => admin_url('moderation?filter=ng_word')],
                ['label' => 'お問い合わせ（未対応）', 'count' => $display->number($todos['inquiries']), 'url' => admin_url('inquiries?status=todo')],
                ['label' => 'AI判定（未判定）', 'count' => $display->number($todos['aiPending']), 'url' => admin_url('moderation?filter=ai_review')],
            ],
        ]);
    }
}
