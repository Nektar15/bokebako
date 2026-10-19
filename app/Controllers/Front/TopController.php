<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * U-01 トップ・大喜利開催
 */
final class TopController extends BaseController
{
    /** U-01 トップ・大喜利開催 */
    public function index(): string
    {
        return $this->placeholder('U-01', 'トップ・大喜利開催');
    }

    /** U-02 大喜利の作成（同意して開催する） — TODO(inner): 工程4で本実装 */
    public function create(): ResponseInterface
    {
        return redirect()->to('/');
    }

}
