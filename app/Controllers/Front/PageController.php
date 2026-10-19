<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 静的ページ U-14〜U-17
 */
final class PageController extends BaseController
{
    /** U-14 サービス紹介・遊び方 */
    public function about(): string
    {
        return $this->placeholder('U-14', 'サービス紹介・遊び方');
    }

    /** U-15 利用規約 */
    public function terms(): string
    {
        return $this->placeholder('U-15', '利用規約');
    }

    /** U-16 プライバシーポリシー */
    public function privacy(): string
    {
        return $this->placeholder('U-16', 'プライバシーポリシー');
    }

    /** U-17 投稿ガイドライン */
    public function guidelines(): string
    {
        return $this->placeholder('U-17', '投稿ガイドライン');
    }

}
