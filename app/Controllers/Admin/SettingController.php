<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-10 運用設定
 */
final class SettingController extends BaseController
{
    /**
     * A-10 運用設定
     */
    public function index(): string
    {
        return $this->placeholder('A-10', '運用設定');
    }

    /**
     * A-10 保存 — TODO(inner): 工程4で本実装
     */
    public function update(): ResponseInterface
    {
        return redirect()->to('/');
    }
}
