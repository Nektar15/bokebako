<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A-09 お問い合わせ管理
 */
final class InquiryController extends BaseController
{
    /** A-09 お問い合わせ管理 */
    public function index(): string
    {
        return $this->placeholder('A-09', 'お問い合わせ管理');
    }

    /** A-09 ステータス・メモの更新 — TODO(inner): 工程4で本実装 */
    public function update(int $id): ResponseInterface
    {
        return redirect()->to('/');
    }

}
