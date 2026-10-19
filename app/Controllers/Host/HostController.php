<?php

declare(strict_types=1);

namespace App\Controllers\Host;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * U-08 開催者専用画面
 */
final class HostController extends BaseController
{
    /** U-08 開催者専用 */
    public function show(string $slug): string
    {
        return $this->placeholder('U-08', '開催者専用');
    }

    /** U-08 合言葉で本人確認 — TODO(inner): 工程4で本実装 */
    public function recover(string $slug): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** U-08 お気に入りの切り替え — TODO(inner): 工程4で本実装 */
    public function favorite(string $slug, int $answerId): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** U-09 結果の確定 — TODO(inner): 工程4で本実装 */
    public function decide(string $slug): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** U-08 大喜利の削除 — TODO(inner): 工程4で本実装 */
    public function delete(string $slug): ResponseInterface
    {
        return redirect()->to('/');
    }

}
