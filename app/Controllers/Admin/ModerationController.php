<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-05 通報・モデレーション
 */
final class ModerationController extends BaseController
{
    /** A-05 通報・モデレーション */
    public function index(): string
    {
        return $this->placeholder('A-05', '通報・モデレーション');
    }

    /** A-05 問題なし（処理済みに） — TODO(inner): 工程4で本実装 */
    public function resolve(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** A-05 非表示 — TODO(inner): 工程4で本実装 */
    public function hide(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** A-05 削除 — TODO(inner): 工程4で本実装 */
    public function delete(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** A-05 投稿元の制限 — TODO(inner): 工程4で本実装 */
    public function restrict(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

}
