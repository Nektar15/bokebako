<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Exceptions\NotFoundException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * 画面一覧ナビ（確認用。ADR 0009）。開催者・回答者として開く画面へ、準備をしてから移動する
 */
final class ScreenNavController extends BaseController
{
    public function go(): RedirectResponse
    {
        try {
            $url = service('screenNav')->go((int) $this->request->getGet('to'));
        } catch (NotFoundException) {
            throw PageNotFoundException::forPageNotFound();
        }

        return redirect()->to($url)->withCookies();
    }
}
