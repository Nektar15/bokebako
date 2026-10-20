<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\AdminAccount;

/**
 * 管理者アカウント（A-01）（admins テーブル）の読み書き
 *
 * @method AdminAccount|null  find(int|string|null $id = null)
 * @method list<AdminAccount> findAll(int $limit = 0, int $offset = 0)
 * @method AdminAccount|null  first()
 */
final class AdminModel extends BaseModel
{
    protected $table         = 'admins';
    protected $returnType    = AdminAccount::class;
    protected $allowedFields = ['login_id', 'name', 'password_hash', 'role', 'totp_secret', 'failed_count', 'locked_until', 'last_login_at'];
}
