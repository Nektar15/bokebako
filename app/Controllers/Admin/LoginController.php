<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * A-01 管理者ログイン・ログアウト
 *
 * ログイン画面は adminAuth の外（IP 制限だけ）。左メニューのバッジなどは要らないので AdminController を継がない。
 */
final class LoginController extends BaseController
{
    public function show(): RedirectResponse|string
    {
        if (service('adminAuth')->current() !== null) {
            return redirect()->to(admin_url());
        }

        return view('admin/a01_login', [
            'screenId' => 'A-01',
            'title'    => 'ログイン',
            'guest'    => true,
            'loginId'  => (string) old('login_id', ''),
            'notice'   => session()->getFlashdata('notice'),
            'errors'   => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function login(): RedirectResponse
    {
        $auth = service('adminAuth');

        try {
            $account = $auth->verifyPassword((string) $this->request->getPost('login_id'), (string) $this->request->getPost('password'));
        } catch (ValidationException $e) {
            return redirect()->to(admin_url('login'))->withInput()->with('errors', $e->errors);
        } catch (AppException $e) {
            return redirect()->to(admin_url('login'))->withInput()->with('errors', ['login' => $e->getMessage()]);
        }
        $auth->startSession($account);

        return redirect()->to(admin_url());
    }

    public function logout(): RedirectResponse
    {
        service('adminAuth')->logout();

        return redirect()->to(admin_url('login'))->with('notice', 'ログアウトしました');
    }
}
