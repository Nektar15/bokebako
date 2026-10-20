<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\NgWord;
use App\Enums\NgWordAction;
use App\Enums\NgWordMatchType;
use App\Exceptions\ValidationException;
use App\Services\NgWordService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-06 NGワード管理（一覧・追加・編集・削除・CSV）
 */
final class NgWordController extends AdminController
{
    /**
     * A-06（?q= ?action= ?category= で絞り込み、?edit={id} で右側のフォームを編集にする）
     */
    public function index(): string
    {
        $service = service('ngWord');
        $display = service('display');
        $actions = ['all' => '処理：すべて'];

        foreach (NgWordAction::cases() as $case) {
            $actions[$case->value] = $case->label();
        }
        $filter = [
            'q'        => $this->text('q'),
            'action'   => $this->choice('action', $actions, 'all'),
            'category' => $this->choice('category', ['all' => 'カテゴリー：すべて'] + array_combine($service->categories(), $service->categories()), 'all'),
        ];
        $editId  = (int) $this->request->getGet('edit');
        $editing = $editId > 0 ? $this->findOrFail(static fn (): NgWord => $service->find($editId)) : null;

        return $this->render('admin/a06_ng_word_list', [
            'screenId'    => 'A-06',
            'title'       => 'NGワード管理',
            'filter'      => $filter,
            'actions'     => $actions,
            'categories'  => $service->categories(),
            'counts'      => $service->counts(),
            'matchTypes'  => NgWordMatchType::cases(),
            'actionCases' => NgWordAction::cases(),
            'items'       => array_map(static fn (NgWord $w) => ['word' => $w, 'updated' => $display->shortDate($w->updated_at)], $service->list($filter)),
            'editing'     => $editing,
            'form'        => $this->formValues($editing),
            'csvColumns'  => implode(', ', NgWordService::CSV_COLUMNS),
        ]);
    }

    /**
     * A-06 追加
     */
    public function create(): RedirectResponse
    {
        try {
            service('ngWord')->create($this->input());
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return redirect()->to(admin_url('ng-words'))->with('notice', 'ワードを追加しました');
    }

    /**
     * A-06 編集（フォームからの保存と、一覧の有効／無効の切り替え）
     */
    public function update(int $id): RedirectResponse
    {
        try {
            $this->orFail(fn () => service('ngWord')->update($id, $this->input()));
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return redirect()->to(admin_url('ng-words'))->with('notice', 'ワードを保存しました');
    }

    /**
     * A-06 削除
     */
    public function delete(int $id): RedirectResponse
    {
        $this->orFail(static fn () => service('ngWord')->delete($id));

        return $this->backWithNotice('ワードを削除しました');
    }

    /**
     * A-06 CSVエクスポート
     */
    public function export(): ResponseInterface
    {
        return $this->response->download('ng-words.csv', service('ngWord')->exportCsv())->setContentType('text/csv; charset=UTF-8');
    }

    /**
     * A-06 CSVインポート
     */
    public function import(): RedirectResponse
    {
        $file = $this->request->getFile('csv');

        try {
            if ($file === null || ! $file->isValid()) {
                throw new ValidationException(['csv' => 'CSV ファイルを選んでください']);
            }
            $count = service('ngWord')->importCsv((string) file_get_contents($file->getTempName()));
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return $this->backWithNotice("{$count} 行を取り込みました");
    }

    /**
     * 右側のフォームの初期値（入力エラーで戻ったときは入力値、編集なら保存済みの値、追加なら既定値）
     *
     * @return array{word: string, match_type: string, category: string, action: string}
     */
    private function formValues(?NgWord $editing): array
    {
        return [
            'word'       => (string) old('word', $editing?->word ?? ''),
            'match_type' => (string) old('match_type', $editing?->match_type ?? NgWordMatchType::Partial->value),
            'category'   => (string) old('category', $editing?->category ?? service('ngWord')->categories()[0]),
            'action'     => (string) old('action', $editing?->action ?? NgWordAction::Review->value),
        ];
    }

    /**
     * フォームの入力（追加・編集で同じ）
     *
     * @return array{word: string, match_type: string, category: string, action: string, enabled: string}
     */
    private function input(): array
    {
        return [
            'word'       => (string) $this->request->getPost('word'),
            'match_type' => (string) $this->request->getPost('match_type'),
            'category'   => (string) $this->request->getPost('category'),
            'action'     => (string) $this->request->getPost('action'),
            'enabled'    => (string) ($this->request->getPost('enabled') ?? '1'),
        ];
    }
}
