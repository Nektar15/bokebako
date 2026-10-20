<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-04 回答管理
 */
final class AnswerController extends BaseController
{
    /**
     * A-04 回答管理
     */
    public function index(): string
    {
        return $this->placeholder('A-04', '回答管理');
    }

    /**
     * A-04 非表示 — TODO(inner): 工程4で本実装
     */
    public function hide(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-04 再公開 — TODO(inner): 工程4で本実装
     */
    public function publish(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-04 削除 — TODO(inner): 工程4で本実装
     */
    public function delete(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }
}
