<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\ModerationItem;
use App\Enums\ModerationFlag;
use App\Services\ModerationService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * A-05 通報・モデレーション（待ち行列と処理）
 */
final class ModerationController extends AdminController
{
    /**
     * A-05（?filter= で絞り込み、?item= で右側に出す1件を選ぶ。未指定なら先頭）
     */
    public function index(): string
    {
        $filter  = $this->choice('filter', ModerationService::FILTERS, 'all');
        $service = service('moderation');
        $display = service('display');
        $queue   = $service->queue($filter);
        $counts  = $service->counts();
        $tabs    = [];

        foreach (ModerationService::FILTERS as $value => $label) {
            $tabs[] = ['label' => $label, 'count' => $display->number($counts[$value] ?? 0), 'url' => admin_url('moderation?filter=' . $value), 'active' => $value === $filter];
        }
        $selectedKey = $this->text('item');
        $selected    = null;

        foreach ($queue as $item) {
            if ($selected === null || $item->key() === $selectedKey) {
                $selected = $item;
            }
        }

        return $this->render('admin/a05_moderation', [
            'screenId'      => 'A-05',
            'title'         => '通報・モデレーション',
            'tabs'          => $tabs,
            'queue'         => array_map(fn (ModerationItem $i) => $this->queueRow($i, $selected, $filter), $queue),
            'selected'      => $selected,
            'selectedAt'    => $selected !== null ? $display->datetime($selected->created_at) : '',
            'history'       => $selected !== null ? $display->logs($selected->history) : [],
            'restrictHours' => ModerationService::RESTRICT_HOURS,
        ]);
    }

    /**
     * A-05 問題なし（通報を処理済みに）
     */
    public function resolve(string $key): RedirectResponse
    {
        $this->orFail(static fn () => service('moderation')->resolve($key));

        return $this->backWithNotice('処理済みにしました');
    }

    /**
     * A-05 非表示
     */
    public function hide(string $key): RedirectResponse
    {
        $this->orFail(static fn () => service('moderation')->hide($key));

        return $this->backWithNotice('非表示にしました');
    }

    /**
     * A-05 削除
     */
    public function delete(string $key): RedirectResponse
    {
        $this->orFail(static fn () => service('moderation')->delete($key));

        return $this->backWithNotice('削除しました');
    }

    /**
     * A-05 投稿元の制限（hours は ModerationService::RESTRICT_HOURS のキー）
     */
    public function restrict(string $key): RedirectResponse
    {
        $hours = $this->choicePost('hours', ModerationService::RESTRICT_HOURS, '24');
        $this->orFail(static fn () => service('moderation')->restrict($key, (int) $hours));

        return $this->backWithNotice('投稿元を' . ModerationService::RESTRICT_HOURS[$hours] . '制限しました');
    }

    /**
     * 左の一覧の1件（表示用に整形）
     *
     * @return array{item: ModerationItem, url: string, active: bool, time: string, flags: list<array{value: string, label: string}>, stateLabel: string}
     */
    private function queueRow(ModerationItem $item, ?ModerationItem $selected, string $filter): array
    {
        return [
            'item'       => $item,
            'url'        => admin_url('moderation?filter=' . $filter . '&item=' . $item->key()),
            'active'     => $selected !== null && $item->key() === $selected->key(),
            'time'       => service('display')->time($item->created_at),
            'flags'      => array_map(static fn (ModerationFlag $f) => ['value' => $f->value, 'label' => $f->label()], $item->flags()),
            'stateLabel' => $item->targetStatusLabel(),
        ];
    }
}
