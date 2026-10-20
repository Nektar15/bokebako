<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 管理画面のログイン確認（A-01）
 *
 * ログイン中なら「現在の管理者」（CurrentAdminService）を用意する。Controller と Service はそこから管理者ID・権限を受け取る。
 * 未ログイン・期限切れならログイン画面へ。IP 制限は手前の adminIp Filter。
 */
class AdminAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $account = service('adminAuth')->current();

        if ($account === null) {
            return redirect()->to(admin_url('login'))->with('notice', 'ログインしてください');
        }
        service('currentAdmin')->set($account->toAdminUser());

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ログイン中の画面はブラウザ・中継サーバーに残さない（ログアウト後に「戻る」で見えないように）
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        return null;
    }
}
