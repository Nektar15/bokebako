<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AdminRole;

/**
 * ログイン中の管理者
 *
 * A-01 実装後は admins テーブルの行から作る。仮認証（CODING_RULES 7-1）でも同じ形で渡す。
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
