<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 残り時間・回答数・状態（JSON）
 */
final class StatusController extends BaseController
{
    /** GET /api/v1/o/{slug}/status — TODO(inner): 工程4で本実装 */
    public function show(string $slug): ResponseInterface
    {
        return $this->response->setJSON(['ok' => true, 'data' => ['slug' => $slug, 'status' => 'open', 'remainingSeconds' => 0, 'answerCount' => 0]]);
    }
}
