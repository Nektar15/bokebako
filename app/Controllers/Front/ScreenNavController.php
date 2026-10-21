<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Exceptions\NotFoundException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * 画面一覧ナビ（確認用。ADR 0009）。プルダウンで選んだ画面へ移動する（JS がなくても「移動」ボタンで動く）
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
