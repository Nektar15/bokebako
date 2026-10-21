<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Enums\OgpKind;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * GET /api/v1/ogp/{kind}/{key} — OGP 画像に載せる中身（Vercel の画像生成 api/og.js が読む。ADR 0014）
 *
 * 公開中のお題・回答だけを返す（ページで見えるものと同じ）。非表示・削除中は 404
 */
final class OgpController extends BaseController
{
    public function show(string $kind, string $key): ResponseInterface
    {
        $ogpKind = OgpKind::tryFrom($kind);
        $data    = $ogpKind === null ? null : service('ogp')->data($ogpKind, $key);

        if ($data === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => '見つかりません']]);
        }

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['ok' => true, 'data' => $data]);
    }
}
