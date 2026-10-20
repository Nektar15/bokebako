<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\Ad;
use App\Enums\AdSlot;
use App\Exceptions\ValidationException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * A-07 広告管理（一覧・登録・編集・削除）
 */
final class AdController extends AdminController
{
    /**
     * A-07（?edit={id} で右側のフォームを編集にする）
     */
    public function index(): string
    {
        $service = service('ads');
        $display = service('display');
        $editId  = (int) $this->request->getGet('edit');
        $editing = $editId > 0 ? $this->findOrFail(static fn (): Ad => $service->find($editId)) : null;

        return $this->render('admin/a07_ad_list', [
            'screenId' => 'A-07',
            'title'    => '広告管理',
            'slots'    => $this->slotCards(),
            'items'    => array_map(static fn (Ad $ad) => [
                'ad'          => $ad,
                'period'      => $display->shortDate($ad->starts_at) . '〜' . $display->shortDate($ad->ends_at),
                'impressions' => $display->number($ad->impressions),
                'clicks'      => $display->number($ad->clicks),
                'ctr'         => $display->percent($ad->clicks, $ad->impressions),
            ], $service->list()),
            'editing'   => $editing,
            'slotCases' => AdSlot::cases(),
            'form'      => $this->formValues($editing),
        ]);
    }

    /**
     * A-07 登録（action=publish で公開、それ以外は下書き）
     */
    public function create(): RedirectResponse
    {
        try {
            service('ads')->create($this->input(), $this->request->getFile('image'));
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return redirect()->to(admin_url('ads'))->with('notice', $this->request->getPost('action') === 'publish' ? '広告を公開しました' : '下書きを保存しました');
    }

    /**
     * A-07 編集（公開・停止もここ）
     */
    public function update(int $id): RedirectResponse
    {
        try {
            $this->orFail(fn () => service('ads')->update($id, $this->input(), $this->request->getFile('image')));
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return redirect()->to(admin_url('ads'))->with('notice', '広告を保存しました');
    }

    /**
     * A-07 削除
     */
    public function delete(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('ads')->delete($id));

        return redirect()->to(admin_url('ads'))->with('notice', '広告を削除しました');
    }

    /**
     * 枠ごとの今月の実績（表示用に整形）
     *
     * @return list<array{label: string, live: string, impressions: string, clicks: string, ctr: string}>
     */
    private function slotCards(): array
    {
        $display = service('display');
        $cards   = [];

        foreach (service('ads')->slotSummary() as $slot => $summary) {
            $cards[] = [
                'label'       => AdSlot::from($slot)->label() . '広告枠',
                'live'        => '掲載中 ' . $summary['live'] . '本（ローテーション）',
                'impressions' => $display->number($summary['impressions']),
                'clicks'      => $display->number($summary['clicks']),
                'ctr'         => $display->percent($summary['clicks'], $summary['impressions']),
            ];
        }

        return $cards;
    }

    /**
     * 右側のフォームの初期値（入力エラーで戻ったときは入力値、編集なら保存済みの値）
     *
     * @return array{name: string, sponsor: string, url: string, slot: string, starts_at: string, ends_at: string}
     */
    private function formValues(?Ad $editing): array
    {
        $display = service('display');

        return [
            'name'      => (string) old('name', $editing?->name ?? ''),
            'sponsor'   => (string) old('sponsor', $editing?->sponsor ?? ''),
            'url'       => (string) old('url', $editing?->url ?? ''),
            'slot'      => (string) old('slot', $editing?->slot ?? AdSlot::Top->value),
            'starts_at' => (string) old('starts_at', $editing !== null ? $display->inputDatetime($editing->starts_at) : ''),
            'ends_at'   => (string) old('ends_at', $editing !== null ? $display->inputDatetime($editing->ends_at) : ''),
        ];
    }

    /**
     * フォームの入力（登録・編集で同じ。画像は Service で $this->request->getFile('image') を見ずに済むよう別に渡す：工程4）
     *
     * @return array{name: string, sponsor: string, url: string, slot: string, starts_at: string, ends_at: string, action: string}
     */
    private function input(): array
    {
        return [
            'name'      => (string) $this->request->getPost('name'),
            'sponsor'   => (string) $this->request->getPost('sponsor'),
            'url'       => (string) $this->request->getPost('url'),
            'slot'      => (string) $this->request->getPost('slot'),
            'starts_at' => (string) $this->request->getPost('starts_at'),
            'ends_at'   => (string) $this->request->getPost('ends_at'),
            'action'    => (string) $this->request->getPost('action'),
        ];
    }
}
