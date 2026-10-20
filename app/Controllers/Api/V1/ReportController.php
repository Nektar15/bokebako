<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 通報の受付（S-04）
 */
final class ReportController extends BaseController
{
    /**
     * POST /api/v1/reports — TODO(inner): 工程4で本実装
     */
    public function create(): ResponseInterface
    {
        return $this->response->setJSON(['ok' => true, 'data' => null]);
    }
}
