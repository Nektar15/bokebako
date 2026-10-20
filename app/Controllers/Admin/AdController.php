<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-07 広告管理
 */
final class AdController extends BaseController
{
    /**
     * A-07 広告管理
     */
    public function index(): string
    {
        return $this->placeholder('A-07', '広告管理');
    }

    /**
     * A-07 登録 — TODO(inner): 工程4で本実装
     */
    public function create(): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-07 編集 — TODO(inner): 工程4で本実装
     */
    public function update(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-07 削除 — TODO(inner): 工程4で本実装
     */
    public function delete(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }
}
