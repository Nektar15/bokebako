<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\Inquiry;
use App\Enums\InquiryStatus;
use App\Exceptions\ValidationException;
use App\Services\InquiryService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * A-09 お問い合わせ管理（一覧・詳細・対応状況・対応メモ）
 */
final class InquiryController extends AdminController
{
    /**
     * A-09（?status= ?type= ?period= で絞り込み、?item={id} で右側に出す1件を選ぶ。未指定なら先頭）
     */
    public function index(): string
    {
        $service  = service('inquiry');
        $display  = service('display');
        $statuses = ['all' => 'すべて'];

        foreach (InquiryStatus::cases() as $case) {
            $statuses[$case->value] = $case->label();
        }
        $filter = [
            'status' => $this->choice('status', $statuses, 'all'),
            'type'   => $this->choice('type', ['all' => '種別：すべて'] + $service->types(), 'all'),
            ...$this->periodFilter(InquiryService::PERIODS),
        ];
        $items    = $service->list($filter);
        $selected = null;

        foreach ($items as $item) {
            if ($selected === null || $item->id === (int) $this->request->getGet('item')) {
                $selected = $item;
            }
        }

        return $this->render('admin/a09_inquiry_list', [
            'screenId'    => 'A-09',
            'title'       => 'お問い合わせ管理',
            'filter'      => $filter,
            'types'       => $service->types(),
            'periods'     => InquiryService::PERIODS,
            'tabs'        => $this->statusTabs($statuses, $filter),
            'items'       => array_map(fn (Inquiry $i) => ['inquiry' => $i, 'time' => $display->short($i->created_at), 'url' => admin_url('inquiries' . $this->query(['item' => (string) $i->id] + $filter)), 'active' => $selected !== null && $i->id === $selected->id], $items),
            'selected'    => $selected,
            'selectedAt'  => $selected !== null ? $display->full($selected->created_at) : '',
            'memos'       => $selected !== null ? $display->logs($selected->memos) : [],
            'statusCases' => InquiryStatus::cases(),
        ]);
    }

    /**
     * A-09 対応状況の変更・対応メモの追加
     */
    public function update(int $id): RedirectResponse
    {
        try {
            $this->orFail(fn () => service('inquiry')->update($id, [
                'status' => (string) $this->request->getPost('status'),
                'memo'   => (string) $this->request->getPost('memo'),
            ]));
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return $this->backWithNotice('保存しました');
    }

    /**
     * 対応状況のタブ（未対応・対応中・対応済み・すべて の順。partials/admin_tabs 用）
     *
     * @param array<string, string>                               $statuses
     * @param array{status: string, type: string, period: string} $filter
     *
     * @return list<array{label: string, count: string|null, url: string, active: bool}>
     */
    private function statusTabs(array $statuses, array $filter): array
    {
        $counts  = service('inquiry')->counts();
        $display = service('display');
        $tabs    = [];

        foreach (['todo', 'doing', 'done', 'all'] as $value) {
            $tabs[] = [
                'label'  => $statuses[$value],
                'count'  => $display->number($counts[$value] ?? 0),
                'url'    => admin_url('inquiries' . $this->query(['status' => $value] + $filter)),
                'active' => $value === $filter['status'],
            ];
        }

        return $tabs;
    }
}
