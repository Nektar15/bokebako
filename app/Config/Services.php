<?php

declare(strict_types=1);

namespace Config;

use App\Services\CurrentAdminService;
use CodeIgniter\Config\BaseService;

/**
 * アプリ固有の Service 登録（CODING_RULES 3章）
 *
 * Controller は `service('xxx')` で取得する。`new` しない。
 * 工程4で本実装する Service はここに追記していく。
 */
class Services extends BaseService
{
    public static function currentAdmin(bool $getShared = true): CurrentAdminService
    {
        if ($getShared) {
            return static::getSharedInstance('currentAdmin');
        }

        return new CurrentAdminService();
    }
}
