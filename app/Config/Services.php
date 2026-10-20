<?php

declare(strict_types=1);

namespace Config;

use App\Libraries\Captcha;
use App\Libraries\OgpImage;
use App\Models\AdminLogModel;
use App\Models\AdModel;
use App\Models\AiJudgementModel;
use App\Models\AnswerModel;
use App\Models\DailyStatModel;
use App\Models\EventModel;
use App\Models\InquiryModel;
use App\Models\NgWordModel;
use App\Models\OgpJobModel;
use App\Models\OogiriModel;
use App\Models\ReportModel;
use App\Models\RestrictionModel;
use App\Services\AdminLogService;
use App\Services\AdService;
use App\Services\AnswerAdminService;
use App\Services\AnswerService;
use App\Services\ContactService;
use App\Services\CurrentAdminService;
use App\Services\DisplayService;
use App\Services\EventService;
use App\Services\HostService;
use App\Services\IdentityService;
use App\Services\InquiryService;
use App\Services\LifecycleService;
use App\Services\MailService;
use App\Services\ModerationService;
use App\Services\NgWordService;
use App\Services\OgpService;
use App\Services\OogiriAdminService;
use App\Services\OogiriService;
use App\Services\PageCacheService;
use App\Services\RateLimitService;
use App\Services\ReportService;
use App\Services\SettingService;
use App\Services\ShareService;
use App\Services\StatsService;
use CodeIgniter\Config\BaseService;

/**
 * アプリ固有の Service 登録（CODING_RULES 3章）
 *
 * Controller は `service('xxx')` で取得する。`new` しない。Model もここで組み立てて渡す。
 */
class Services extends BaseService
{
    // ---- 共通 ----

    public static function currentAdmin(bool $getShared = true): CurrentAdminService
    {
        return $getShared ? static::getSharedInstance('currentAdmin') : new CurrentAdminService();
    }

    public static function settings(bool $getShared = true): SettingService
    {
        return $getShared ? static::getSharedInstance('settings') : new SettingService();
    }

    public static function display(bool $getShared = true): DisplayService
    {
        return $getShared ? static::getSharedInstance('display') : new DisplayService();
    }

    public static function identity(bool $getShared = true): IdentityService
    {
        return $getShared ? static::getSharedInstance('identity') : new IdentityService();
    }

    public static function rateLimit(bool $getShared = true): RateLimitService
    {
        return $getShared ? static::getSharedInstance('rateLimit') : new RateLimitService(static::settings(), static::identity(), new RestrictionModel());
    }

    public static function adminLog(bool $getShared = true): AdminLogService
    {
        return $getShared ? static::getSharedInstance('adminLog') : new AdminLogService(new AdminLogModel());
    }

    public static function event(bool $getShared = true): EventService
    {
        return $getShared ? static::getSharedInstance('event') : new EventService(new EventModel(), static::identity());
    }

    public static function pageCache(bool $getShared = true): PageCacheService
    {
        return $getShared ? static::getSharedInstance('pageCache') : new PageCacheService();
    }

    public static function mail(bool $getShared = true): MailService
    {
        return $getShared ? static::getSharedInstance('mail') : new MailService();
    }

    public static function ngWord(bool $getShared = true): NgWordService
    {
        return $getShared ? static::getSharedInstance('ngWord') : new NgWordService(new NgWordModel());
    }

    public static function share(bool $getShared = true): ShareService
    {
        return $getShared ? static::getSharedInstance('share') : new ShareService(static::settings());
    }

    public static function ogp(bool $getShared = true): OgpService
    {
        return $getShared ? static::getSharedInstance('ogp') : new OgpService(new OgpImage(), new OgpJobModel(), new OogiriModel(), new AnswerModel());
    }

    public static function moderation(bool $getShared = true): ModerationService
    {
        return $getShared ? static::getSharedInstance('moderation') : new ModerationService(new AiJudgementModel(), new ReportModel(), new OogiriModel(), new AnswerModel(), static::settings());
    }

    public static function lifecycle(bool $getShared = true): LifecycleService
    {
        return $getShared ? static::getSharedInstance('lifecycle') : new LifecycleService(new OogiriModel(), static::settings());
    }

    // ---- ユーザー向け（U-01〜U-18） ----

    public static function oogiri(bool $getShared = true): OogiriService
    {
        return $getShared ? static::getSharedInstance('oogiri') : new OogiriService(new OogiriModel(), static::settings(), static::identity(), static::ngWord(), static::rateLimit());
    }

    public static function answer(bool $getShared = true): AnswerService
    {
        return $getShared ? static::getSharedInstance('answer') : new AnswerService(new AnswerModel(), new OogiriModel(), static::settings(), static::display(), static::identity(), static::ngWord(), static::rateLimit());
    }

    public static function host(bool $getShared = true): HostService
    {
        return $getShared ? static::getSharedInstance('host') : new HostService(new OogiriModel(), new AnswerModel(), static::identity(), static::rateLimit());
    }

    public static function contact(bool $getShared = true): ContactService
    {
        return $getShared ? static::getSharedInstance('contact') : new ContactService(new InquiryModel(), static::rateLimit(), new Captcha());
    }

    public static function ads(bool $getShared = true): AdService
    {
        return $getShared ? static::getSharedInstance('ads') : new AdService(new AdModel());
    }

    public static function report(bool $getShared = true): ReportService
    {
        return $getShared ? static::getSharedInstance('report') : new ReportService(new ReportModel(), new OogiriModel(), new AnswerModel(), static::identity(), static::rateLimit(), static::settings());
    }

    // ---- 管理画面（A-02〜A-10） ----

    public static function stats(bool $getShared = true): StatsService
    {
        return $getShared ? static::getSharedInstance('stats') : new StatsService(static::display(), new DailyStatModel(), new EventModel(), new OogiriModel(), new AnswerModel(), new AiJudgementModel(), new InquiryModel());
    }

    public static function oogiriAdmin(bool $getShared = true): OogiriAdminService
    {
        return $getShared ? static::getSharedInstance('oogiriAdmin') : new OogiriAdminService(new OogiriModel(), new AnswerModel());
    }

    public static function answerAdmin(bool $getShared = true): AnswerAdminService
    {
        return $getShared ? static::getSharedInstance('answerAdmin') : new AnswerAdminService(new AnswerModel(), new OogiriModel());
    }

    public static function inquiry(bool $getShared = true): InquiryService
    {
        return $getShared ? static::getSharedInstance('inquiry') : new InquiryService(new InquiryModel());
    }
}
