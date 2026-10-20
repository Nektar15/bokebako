<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AdminRole;

/**
 * ログイン中の管理者
 *
 * admins テーブルの行（AdminAccount::toAdminUser()）から、adminAuth Filter が作る。
 */
final class AdminUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly AdminRole $role,
    ) {
    }
}
