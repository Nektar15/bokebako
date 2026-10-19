<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 広告クリック計測（A-07）
 */
final class AdController extends BaseController
{
    /** A-07 クリックを記録してリンク先へリダイレクト — TODO(inner): 工程4で本実装 */
    public function click(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

}
