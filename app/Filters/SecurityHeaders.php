<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\SecureHeaders;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\App;

/**
 * セキュリティヘッダー（SPEC 3.4）。CodeIgniter の SecureHeaders に HSTS と CSP を足す
 *
 * CSP の許可先は Config\App::$cspSources（Google Fonts、GA4、Turnstile）。
 * HSTS は HTTPS が強制される production でだけ出す（ローカルの http で固定されると困るため）。
 */
class SecurityHeaders extends SecureHeaders
{
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        parent::after($request, $response, $arguments);
        $app = config(App::class);

        if ($app->forceGlobalSecureRequests) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        // development では Debug Toolbar がインラインの script/style を差し込むため CSP を出さない（testing・production では出す）
        if (ENVIRONMENT !== 'development') {
            $csp = [];

            foreach ($app->cspSources as $directive => $sources) {
                $csp[] = $directive . ' ' . implode(' ', $sources);
            }
            $response->setHeader('Content-Security-Policy', implode('; ', $csp));
        }
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
