<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 回答まわり
 */
final class AnswerController extends BaseController
{
    /** U-05 回答の登録（同意して回答する） — TODO(inner): 工程4で本実装 */
    public function create(string $slug): ResponseInterface
    {
        return redirect()->to('/');
    }

    /** U-06 回答完了 */
    public function done(string $slug): string
    {
        return $this->placeholder('U-06', '回答完了');
    }

    /** U-07 回答後・回答一覧 */
    public function index(string $slug): string
    {
        return $this->placeholder('U-07', '回答後・回答一覧');
    }

    /** U-04 回答シェア用（回答を1件添えて表示。未確定事項5） */
    public function share(string $slug, int $answerId): string
    {
        return $this->placeholder('U-04', '回答シェア用（回答を1件添えて表示。未確定事項5）');
    }

}
