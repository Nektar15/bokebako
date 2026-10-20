<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\ErrorCode;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * JSON API（/api/v1/*）の POST は、ページの JS（lib/api.js・sendBeacon）からだけ受け付ける
 *
 * ページキャッシュで配信される結果ページ（U-10〜U-12）は CSRF トークンを埋め込めないため、
 * API は CSRF フィルタの対象外にし、代わりに「JSON 本文」か「X-Requested-With ヘッダー」を要求する（ADR 0005）。
 * 他サイトのフォームからは JSON も独自ヘッダーも送れず、fetch は CORS の事前確認で止まる。
 */
class AjaxOnly implements FilterInterface
{
    public const HEADER = 'X-Requested-With';

    /**
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->getMethod() === 'GET') {
            return null;
        }
        $contentType = (string) $request->getHeaderLine('Content-Type');
        $requested   = (string) $request->getHeaderLine(self::HEADER);

        if (str_starts_with($contentType, 'application/json') || $requested === 'XMLHttpRequest') {
            return null;
        }

        return service('response')->setStatusCode(400)->setJSON([
            'ok'    => false,
            'error' => ['code' => ErrorCode::ValidationFailed->value, 'message' => ErrorCode::ValidationFailed->message()],
        ]);
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
