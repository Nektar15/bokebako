<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * このブラウザで開催した大喜利の一覧（D-02、ADR 0015）。トークンを送るので POST
 */
final class HostedController extends BaseController
{
    /**
     * POST /api/v1/hosted {items: [{slug, token}]} → {items: [{slug, odai, status, statusLabel, answerCount, startsAt}]}
     */
    public function index(): ResponseInterface
    {
        try {
            $json = $this->request->getJSON(true); // apiBody() は文字列と数だけを通すので、配列はここで読む
        } catch (Throwable) {
            $json = null;
        }
        $items = is_array($json) ? ($json['items'] ?? []) : [];

        return $this->response->setJSON(['ok' => true, 'data' => ['items' => service('host')->hostedList(is_array($items) ? array_values(array_filter($items, 'is_array')) : [])]]);
    }
}
