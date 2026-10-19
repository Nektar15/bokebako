<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * U-18 お問い合わせ
 */
final class ContactController extends BaseController
{
    /** U-18 お問い合わせ */
    public function index(): string
    {
        return $this->placeholder('U-18', 'お問い合わせ');
    }

    /** U-18 お問い合わせの送信 — TODO(inner): 工程4で本実装 */
    public function send(): ResponseInterface
    {
        return redirect()->to('/contact/done');
    }

    /** U-18 お問い合わせ送信完了 */
    public function done(): string
    {
        return $this->placeholder('U-18', 'お問い合わせ送信完了');
    }

}
