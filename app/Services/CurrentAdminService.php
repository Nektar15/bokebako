<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminUser;
use RuntimeException;

/**
 * 現在の管理者の置き場所
 *
 * adminAuth Filter が set() し、Controller と Service は get() で受け取る。
 * 認証方法（仮認証／本認証）が変わっても、ここから先は変えない。
 */
final class CurrentAdminService
{
    private ?AdminUser $admin = null;

    public function set(AdminUser $admin): void
    {
        $this->admin = $admin;
    }

    public function get(): AdminUser
    {
        if ($this->admin === null) {
            throw new RuntimeException('管理者が設定されていません。adminAuth Filter の内側で呼んでください');
        }

        return $this->admin;
    }

    public function has(): bool
    {
        return $this->admin !== null;
    }
}
