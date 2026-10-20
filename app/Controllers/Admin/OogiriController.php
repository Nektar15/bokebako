<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\Answer;
use App\Entities\Oogiri;
use App\Enums\OogiriStatus;
use App\Exceptions\ValidationException;
use App\Services\OogiriAdminService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * A-03 大喜利管理（一覧・詳細・操作）
 */
final class OogiriController extends AdminController
{
    /**
     * A-03 一覧（?q= ?period= ?sort= ?status= ?page=）
     */
    public function index(): string
    {
        $filter = [
            'q'      => $this->text('q'),
            'period' => $this->choice('period', OogiriAdminService::PERIODS, 'all'),
            'sort'   => $this->choice('sort', OogiriAdminService::SORTS, 'new'),
            'status' => $this->choice('status', $this->statuses(), 'all'),
        ];
        $display = service('display');
        $page    = service('oogiriAdmin')->list($filter, $this->page());

        return $this->render('admin/a03_oogiri_list', [
            'screenId' => 'A-03',
            'title'    => '大喜利管理',
            'filter'   => $filter,
            'periods'  => OogiriAdminService::PERIODS,
            'sorts'    => OogiriAdminService::SORTS,
            'tabs'     => $this->statusTabs($filter),
            'items'    => $this->rows($page['items']),
            'pager'    => $display->pager($page, admin_url('oogiri' . $this->query($filter))),
        ]);
    }

    /**
     * A-03 詳細（?edit=1 でベスト回答の代理変更フォームを開く）
     */
    public function show(int $id): string
    {
        $service = service('oogiriAdmin');
        $display = service('display');
        $oogiri  = $this->findOrFail(static fn (): Oogiri => $service->find($id));
        $ranked  = [1 => null, 2 => null, 3 => null];

        foreach ($service->ranked($oogiri) as $answer) {
            $ranked[$answer->rank] = $answer;
        }

        return $this->render('admin/a03_oogiri_detail', [
            'screenId'  => 'A-03',
            'title'     => '大喜利 #' . $oogiri->id,
            'oogiri'    => $oogiri,
            'status'    => $oogiri->status(),
            'isHidden'  => in_array($oogiri->status(), [OogiriStatus::Hidden, OogiriStatus::Deleted], true),
            'createdAt' => $display->datetime($oogiri->created_at),
            'endsAt'    => $display->datetime($oogiri->ends_at),
            'decidedAt' => $oogiri->decided_at !== null ? $display->datetime($oogiri->decided_at) : '—',
            'parentId'  => $oogiri->parent_oogiri_id !== null ? '#' . $oogiri->parent_oogiri_id : '—',
            'stats'     => [
                'PV'       => $display->number($oogiri->pv),
                '回答'     => $display->number($oogiri->answer_count),
                'シェア'   => $display->number($oogiri->share_count),
                '派生開催' => $display->number($oogiri->derived_count),
                '通報'     => $display->number($oogiri->report_count),
            ],
            'ranked'  => $ranked,
            'editing' => $this->request->getGet('edit') === '1',
            'answers' => array_map(static fn (Answer $a) => ['answer' => $a, 'posted' => $display->short($a->created_at)], $service->answers($oogiri)),
            'logs'    => $display->logs($service->logs($oogiri)),
        ]);
    }

    /**
     * A-03 非表示
     */
    public function hide(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('oogiriAdmin')->hide($id));

        return $this->backWithNotice("#{$id} を非表示にしました");
    }

    /**
     * A-03 再公開
     */
    public function publish(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('oogiriAdmin')->publish($id));

        return $this->backWithNotice("#{$id} を再公開しました");
    }

    /**
     * A-03 削除（詳細からでも一覧へ戻る）
     */
    public function delete(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('oogiriAdmin')->delete($id));

        return redirect()->to(admin_url('oogiri'))->with('notice', "#{$id} を削除しました");
    }

    /**
     * A-03 ベスト回答の代理変更（詳細画面の「順位を保存する」）
     */
    public function decide(int $id): RedirectResponse
    {
        try {
            $this->orFail(fn () => service('oogiriAdmin')->decide($id, (array) $this->request->getPost('ranks')));
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return redirect()->to(admin_url('oogiri/' . $id))->with('notice', '順位を保存しました');
    }

    /**
     * A-03 結果ページキャッシュの再生成
     */
    public function regenerateCache(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('oogiriAdmin')->regenerateCache($id));

        return $this->backWithNotice('キャッシュを再生成しました');
    }

    /**
     * 状態タブの選択肢（'all' + OogiriStatus）
     *
     * @return array<string, string>
     */
    private function statuses(): array
    {
        $statuses = ['all' => 'すべて'];

        foreach (OogiriStatus::cases() as $case) {
            $statuses[$case->value] = $case->label();
        }

        return $statuses;
    }

    /**
     * 状態タブ（partials/admin_tabs 用）
     *
     * @param array{q: string, period: string, sort: string, status: string} $filter
     *
     * @return list<array{label: string, count: string|null, url: string, active: bool}>
     */
    private function statusTabs(array $filter): array
    {
        $counts  = service('oogiriAdmin')->counts();
        $display = service('display');
        $tabs    = [];

        foreach ($this->statuses() as $value => $label) {
            $tabs[] = [
                'label'  => $label,
                'count'  => $display->number($counts[$value] ?? 0),
                'url'    => admin_url('oogiri' . $this->query(['status' => $value] + $filter)),
                'active' => $value === $filter['status'],
            ];
        }

        return $tabs;
    }

    /**
     * 一覧の行（日時を整形して渡す）
     *
     * @param list<Oogiri> $items
     *
     * @return list<array{oogiri: Oogiri, created: string, ends: string, pv: string, alert: bool, canPublish: bool}>
     */
    private function rows(array $items): array
    {
        $display = service('display');

        return array_map(static fn (Oogiri $o) => [
            'oogiri'     => $o,
            'created'    => $display->short($o->created_at),
            'ends'       => $display->short($o->ends_at),
            'pv'         => $display->number($o->pv),
            'alert'      => $o->report_count >= OogiriAdminService::REPORT_ALERT,
            'canPublish' => in_array($o->status(), [OogiriStatus::Hidden, OogiriStatus::Deleted], true),
        ], $items);
    }
}
