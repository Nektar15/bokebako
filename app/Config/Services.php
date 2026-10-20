<?php

declare(strict_types=1);

namespace Config;

use App\Services\AdService;
use App\Services\AnswerService;
use App\Services\ContactService;
use App\Services\CurrentAdminService;
use App\Services\DisplayService;
use App\Services\HostService;
use App\Services\OogiriService;
use App\Services\SettingService;
use App\Services\ShareService;
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

    public static function settings(bool $getShared = true): SettingService
    {
        return $getShared ? static::getSharedInstance('settings') : new SettingService();
    }

    public static function display(bool $getShared = true): DisplayService
    {
        return $getShared ? static::getSharedInstance('display') : new DisplayService();
    }

    public static function share(bool $getShared = true): ShareService
    {
        return $getShared ? static::getSharedInstance('share') : new ShareService(static::settings());
    }

    public static function ads(bool $getShared = true): AdService
    {
        return $getShared ? static::getSharedInstance('ads') : new AdService();
    }

    public static function oogiri(bool $getShared = true): OogiriService
    {
        return $getShared ? static::getSharedInstance('oogiri') : new OogiriService(static::settings());
    }

    public static function answer(bool $getShared = true): AnswerService
    {
        return $getShared ? static::getSharedInstance('answer') : new AnswerService(static::settings(), static::display());
    }

    public static function host(bool $getShared = true): HostService
    {
        return $getShared ? static::getSharedInstance('host') : new HostService();
    }

    public static function contact(bool $getShared = true): ContactService
    {
        return $getShared ? static::getSharedInstance('contact') : new ContactService();
    }
}
