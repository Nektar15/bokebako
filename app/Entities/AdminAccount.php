<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AdminRole;
use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 管理者アカウント（admins テーブルの1行。A-01 で使う）。ログイン中の管理者は AdminUser に詰め替える
 *
 * @property int         $failed_count
 * @property int         $id
 * @property Time|null   $last_login_at
 * @property Time|null   $locked_until
 * @property string      $login_id
 * @property string      $name
 * @property string      $password_hash
 * @property string      $role          AdminRole の値
 * @property string|null $totp_secret
 */
final class AdminAccount extends Entity
{
    protected $dates = ['locked_until', 'last_login_at', 'created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'failed_count' => 'int'];

    public function role(): AdminRole
    {
        return AdminRole::from($this->attributes['role']);
    }

    public function toAdminUser(): AdminUser
    {
        return new AdminUser($this->id, $this->name, $this->role());
    }
}
