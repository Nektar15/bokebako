<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 計測イベントの記録（S-06）
 */
final class EventController extends BaseController
{
    /** POST /api/v1/events — TODO(inner): 工程4で本実装 */
    public function create(): ResponseInterface
    {
        return $this->response->setJSON(['ok' => true, 'data' => null]);
    }
}
