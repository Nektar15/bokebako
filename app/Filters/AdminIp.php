<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\AdminAuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 管理画面の IP 制限（SPEC 3.4「IPアドレス制限（任意）」）。ログイン画面を含む管理画面のすべてにかける
 *
 * .env の ADMIN_ALLOWED_IPS（カンマ区切り）に IP があれば通す。空なら制限しない。
 */
class AdminIp implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (AdminAuthService::allowsIp($request->getIPAddress(), (string) env('ADMIN_ALLOWED_IPS', ''))) {
            return null;
        }

        return service('response')->setStatusCode(403)->setBody(view('errors/html/error_403'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
