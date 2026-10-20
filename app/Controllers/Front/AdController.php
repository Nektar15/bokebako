<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/** 広告クリック計測（A-07）：計測用URLを経由してリンク先へリダイレクト */
final class AdController extends BaseController
{
    public function click(int $id): RedirectResponse
    {
        // TODO(inner): クリック数を記録し、ads.link_url へリダイレクトする
        return redirect()->to('/');
    }
}
