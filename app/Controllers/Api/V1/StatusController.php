<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Exceptions\NotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;

/**
 * GET /api/v1/o/{slug}/status — 残り時間・回答数・状態（U-04 / U-07 が1分ごとに呼ぶ軽量 API）
 * 本体 HTML はキャッシュ配信し、変わる値だけここで返す（SPEC 4.1）。
 */
final class StatusController extends BaseController
{
    public function show(string $slug): ResponseInterface
    {
        try {
            $oogiri = service('oogiri')->findBySlug($slug);
        } catch (NotFoundException $e) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => ['code' => $e->errorCode->value, 'message' => $e->getMessage()]]);
        }

        $now = Time::now();

        return $this->response->setJSON(['ok' => true, 'data' => [
            'slug'             => $oogiri->slug,
            'status'           => $oogiri->status()->value,
            'remainingSeconds' => max(0, $oogiri->ends_at->getTimestamp() - $now->getTimestamp()),
            'progressPercent'  => service('display')->progressPercent($oogiri->starts_at, $oogiri->ends_at, $now),
            'answerCount'      => $oogiri->answer_count,
        ]]);
    }
}
