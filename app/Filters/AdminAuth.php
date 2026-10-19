<?php

declare(strict_types=1);

namespace App\Filters;

use App\Entities\AdminUser;
use App\Enums\AdminRole;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 管理画面の認証 Filter
 *
 * TODO(inner): A-01 実装時に本認証へ置き換える。
 * それまでは CODING_RULES 7-1 の仮認証で動かす。
 *   development … 固定の仮管理者（上位管理者）でログイン済みとして扱う
 *   staging     … ADMIN_ALLOWED_IPS に含まれる IP だけ仮管理者。それ以外は 403
 *   production  … 常に 403
 * 判定に迷う場合は拒否する。
 */
class AdminAuth implements FilterInterface
{
    public const DEV_ADMIN_ID   = 0;
    public const DEV_ADMIN_NAME = 'dev-admin';

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! self::allowsTemporaryAdmin($request->getIPAddress())) {
            return service('response')->setStatusCode(403)->setBody(view('errors/html/error_403'));
        }

        service('currentAdmin')->set(new AdminUser(self::DEV_ADMIN_ID, self::DEV_ADMIN_NAME, AdminRole::Super));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    /**
     * 仮管理者を許可するか（環境と IP だけで判定する。Service から切り離してテストしやすくする）
     */
    public static function allowsTemporaryAdmin(string $ip, ?string $environment = null, ?string $allowedIps = null): bool
    {
        $environment ??= ENVIRONMENT;
        $allowedIps ??= (string) env('ADMIN_ALLOWED_IPS', '');

        return match ($environment) {
            'development', 'testing' => true,
            'staging' => in_array($ip, array_filter(array_map('trim', explode(',', $allowedIps))), true),
            default => false,
        };
    }
}
