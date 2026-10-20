<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\Answer;
use App\Enums\AnswerStatus;
use App\Services\AnswerAdminService;
use App\Services\OogiriAdminService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * A-04 回答管理（一覧・操作）
 */
final class AnswerController extends AdminController
{
    /**
     * A-04 一覧（?q= ?oogiri= ?status= ?reports= ?period= ?page=）
     */
    public function index(): string
    {
        $statuses = ['all' => '状態：すべて'];

        foreach (AnswerStatus::cases() as $case) {
            $statuses[$case->value] = $case->label();
        }
        $filter = [
            'q'       => $this->text('q'),
            'oogiri'  => $this->text('oogiri'),
            'status'  => $this->choice('status', $statuses, 'all'),
            'reports' => $this->choice('reports', AnswerAdminService::REPORT_FILTERS, 'all'),
            'period'  => $this->choice('period', OogiriAdminService::PERIODS, 'all'),
        ];
        $display = service('display');
        $page    = service('answerAdmin')->list($filter, $this->page());

        return $this->render('admin/a04_answer_list', [
            'screenId'      => 'A-04',
            'title'         => '回答管理',
            'filter'        => $filter,
            'statuses'      => $statuses,
            'reportFilters' => AnswerAdminService::REPORT_FILTERS,
            'periods'       => OogiriAdminService::PERIODS,
            'canViewSource' => service('currentAdmin')->get()->role->canViewSource(),
            'items'         => array_map(static fn (Answer $a) => [
                'answer'  => $a,
                'posted'  => $display->short($a->created_at),
                'alert'   => $a->report_count >= AnswerAdminService::REPORT_ALERT,
                'visible' => $a->status() === AnswerStatus::Visible,
            ], $page['items']),
            'pager' => $display->pager($page, admin_url('answers' . $this->query($filter))),
        ]);
    }

    /**
     * A-04 非表示
     */
    public function hide(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('answerAdmin')->hide($id));

        return $this->backWithNotice("A-{$id} を非表示にしました");
    }

    /**
     * A-04 再公開
     */
    public function publish(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('answerAdmin')->publish($id));

        return $this->backWithNotice("A-{$id} を再公開しました");
    }

    /**
     * A-04 削除
     */
    public function delete(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('answerAdmin')->delete($id));

        return $this->backWithNotice("A-{$id} を削除しました");
    }
}
