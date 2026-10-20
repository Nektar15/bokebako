<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-08 OGP管理
 */
final class OgpController extends BaseController
{
    /**
     * A-08 OGP管理
     */
    public function index(): string
    {
        return $this->placeholder('A-08', 'OGP管理');
    }

    /**
     * A-08 再生成 — TODO(inner): 工程4で本実装
     */
    public function regenerate(): ResponseInterface
    {
        return redirect()->to('/');
    }

    /**
     * A-08 テンプレート画像の差し替え — TODO(inner): 工程4で本実装
     */
    public function updateTemplate(): ResponseInterface
    {
        return redirect()->to('/');
    }
}
