<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-03 大喜利管理
 */
final class OogiriController extends BaseController
{
    /**
     * A-03 大喜利管理
     */
    public function index(): string
    {
        return $this->placeholder('A-03', '大喜利管理');
    }

    /**
     * A-03 大喜利詳細
     */
    public function show(int $id): string
    {
        return $this->placeholder('A-03', '大喜利詳細');
    }

    /**
     * A-03 非表示 — TODO(inner): 工程4で本実装
     */
    public function hide(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-03 再公開 — TODO(inner): 工程4で本実装
     */
    public function publish(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-03 削除 — TODO(inner): 工程4で本実装
     */
    public function delete(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-03 ベスト回答の代理変更 — TODO(inner): 工程4で本実装
     */
    public function decide(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-03 結果ページキャッシュの再生成 — TODO(inner): 工程4で本実装
     */
    public function regenerateCache(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }
}
