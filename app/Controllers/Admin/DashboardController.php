<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-02 管理ダッシュボード
 */
final class DashboardController extends BaseController
{
    /** A-02 管理ダッシュボード */
    public function index(): string
    {
        return $this->placeholder('A-02', '管理ダッシュボード');
    }

}
