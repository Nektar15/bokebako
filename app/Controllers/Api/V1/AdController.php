<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * GET /api/v1/ads — 広告枠の中身（ページキャッシュで配信する U-10・U-12 用。監査 2026-10-21）
 *
 * 広告を HTML に焼き込むと、キャッシュの24時間はローテーションせず、表示回数も数えられず、停止・期限切れの広告も出続ける。
 * そのページでは枠だけを置き、表示のたびにここから取る（表示回数はここで数える）。
 */
final class AdController extends BaseController
{
    public function index(): ResponseInterface
    {
        $data = [];

        foreach (service('ads')->slots() as $position => $ad) {
            $data[$position] = $ad === null ? null : ['image' => $ad['image'], 'alt' => $ad['alt'], 'href' => site_url('ad/' . $ad['id'] . '/click')];
        }

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['ok' => true, 'data' => $data]);
    }
}
