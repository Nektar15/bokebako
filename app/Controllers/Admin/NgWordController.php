<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-06 NGワード管理
 */
final class NgWordController extends BaseController
{
    /** A-06 NGワード管理 */
    public function index(): string
    {
        return $this->placeholder('A-06', 'NGワード管理');
    }

    /** A-06 追加 — TODO(inner): 工程4で本実装 */
    public function create(): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** A-06 編集 — TODO(inner): 工程4で本実装 */
    public function update(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** A-06 削除 — TODO(inner): 工程4で本実装 */
    public function delete(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** A-06 CSVエクスポート — TODO(inner): 工程4で本実装 */
    public function export(): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** A-06 CSVインポート — TODO(inner): 工程4で本実装 */
    public function import(): ResponseInterface
    {
        return redirect()->to('/');
    }

}
