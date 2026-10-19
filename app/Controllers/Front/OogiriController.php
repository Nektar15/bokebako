<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 募集URL /o/{slug}（状態で U-04/U-10/U-11/U-12/U-13 を出し分け）
 */
final class OogiriController extends BaseController
{
    /** U-04 大喜利回答（状態で出し分け） */
    public function show(string $slug): string
    {
        return $this->placeholder('U-04', '大喜利回答（状態で出し分け）');
    }

    /** U-03 開催完了・Xシェア */
    public function created(string $slug): string
    {
        return $this->placeholder('U-03', '開催完了・Xシェア');
    }

}
