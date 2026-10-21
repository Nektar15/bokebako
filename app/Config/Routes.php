<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;
use Config\ScreenNav;

/**
 * ルート定義（FEATURES.md 1章「URLと画面の対応」が正）
 *
 * 画面ID（U-xx / A-xx / S-xx）を各行に添えている。
 * 管理画面のパスは推測困難な値を .env の app.adminPath に置く。
 *
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(false);

// ---------------------------------------------------------------
// ユーザー向け（Front）
// ---------------------------------------------------------------
$routes->group('', ['namespace' => 'App\Controllers\Front'], static function (RouteCollection $routes): void {
    $routes->get('/', 'TopController::index');                             // U-01（?from={slug} で派生元）
    $routes->post('oogiri', 'TopController::create');                      // U-02 同意後の作成

    $routes->get('o/(:segment)/created', 'OogiriController::created/$1');  // U-03
    $routes->get('o/(:segment)', 'OogiriController::show/$1');             // 状態で U-04/U-10/U-11/U-12/U-13 を出し分け
    $routes->post('o/(:segment)/answers', 'AnswerController::create/$1');  // U-05 同意後の登録
    $routes->get('o/(:segment)/done', 'AnswerController::done/$1');        // U-06
    $routes->get('o/(:segment)/answers', 'AnswerController::index/$1');    // U-07
    $routes->get('o/(:segment)/a/(:num)', 'AnswerController::share/$1/$2'); // 回答シェア用（未確定事項5）

    $routes->get('about', 'PageController::about');                        // U-14
    $routes->get('terms', 'PageController::terms');                        // U-15
    $routes->get('privacy', 'PageController::privacy');                    // U-16
    $routes->get('guidelines', 'PageController::guidelines');              // U-17

    $routes->get('contact', 'ContactController::index');                   // U-18
    $routes->post('contact', 'ContactController::send');
    $routes->get('contact/done', 'ContactController::done');

    $routes->get('ad/(:num)/click', 'AdController::click/$1');             // 広告クリック計測（A-07）
});

// ---------------------------------------------------------------
// 開催者専用（Host）
// ---------------------------------------------------------------
$routes->group('host', ['namespace' => 'App\Controllers\Host'], static function (RouteCollection $routes): void {
    $routes->get('(:segment)', 'HostController::show/$1');                        // U-08
    $routes->post('(:segment)/recover', 'HostController::recover/$1');            // 合言葉で本人確認
    $routes->post('(:segment)/restore', 'HostController::restore/$1');            // localStorage のトークンで Cookie を復元（JS から。ADR 0004）
    $routes->post('(:segment)/favorites/(:num)', 'HostController::favorite/$1/$2');
    $routes->post('(:segment)/result', 'HostController::decide/$1');              // U-09 同意後の確定
    $routes->post('(:segment)/delete', 'HostController::delete/$1');
});

// 画面一覧ナビ（確認用。screenNav.enabled のときだけ。ADR 0009）
if (config(ScreenNav::class)->enabled) {
    $routes->get('_screens/go', 'Front\ScreenNavController::go');
}

// ---------------------------------------------------------------
// API（JSON）
// ---------------------------------------------------------------
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'ajaxOnly'], static function (RouteCollection $routes): void {
    $routes->get('o/(:segment)/status', 'StatusController::show/$1');   // 残り時間・回答数・状態
    $routes->post('events', 'EventController::create');                 // 計測（S-06）
    $routes->post('reports', 'ReportController::create');               // 通報（S-04）
});

// ---------------------------------------------------------------
// 管理画面（Admin）— ログイン画面以外はすべて adminAuth の内側。全体に IP 制限（adminIp）
// ---------------------------------------------------------------
$adminPath = trim((string) env('app.adminPath', 'admin'), '/');

// A-01 ログイン（未ログインで開ける。IP 制限だけかける）
$routes->group($adminPath, ['namespace' => 'App\Controllers\Admin', 'filter' => 'adminIp'], static function (RouteCollection $routes): void {
    $routes->get('login', 'LoginController::show');
    $routes->post('login', 'LoginController::login');
});

$routes->group($adminPath, ['namespace' => 'App\Controllers\Admin', 'filter' => ['adminIp', 'adminAuth']], static function (RouteCollection $routes): void {
    $routes->post('logout', 'LoginController::logout');                 // A-01

    $routes->get('accounts', 'AccountController::index');                // A-01 管理者アカウント（未確定事項10 → ADR 0007）
    $routes->post('accounts', 'AccountController::create');
    $routes->post('accounts/(:num)', 'AccountController::update/$1');
    $routes->post('accounts/(:num)/password', 'AccountController::resetPassword/$1');
    $routes->post('accounts/(:num)/delete', 'AccountController::delete/$1');
    $routes->post('accounts/me/password', 'AccountController::changeOwnPassword');

    $routes->get('/', 'DashboardController::index');                     // A-02
    $routes->get('oogiri', 'OogiriController::index');                   // A-03
    $routes->get('oogiri/(:num)', 'OogiriController::show/$1');
    $routes->post('oogiri/(:num)/hide', 'OogiriController::hide/$1');
    $routes->post('oogiri/(:num)/publish', 'OogiriController::publish/$1');
    $routes->post('oogiri/(:num)/delete', 'OogiriController::delete/$1');
    $routes->post('oogiri/(:num)/result', 'OogiriController::decide/$1');       // 代理でのベスト回答変更
    $routes->post('oogiri/(:num)/cache', 'OogiriController::regenerateCache/$1');

    $routes->get('answers', 'AnswerController::index');                  // A-04
    $routes->post('answers/(:num)/hide', 'AnswerController::hide/$1');
    $routes->post('answers/(:num)/publish', 'AnswerController::publish/$1');
    $routes->post('answers/(:num)/delete', 'AnswerController::delete/$1');

    $routes->get('moderation', 'ModerationController::index');           // A-05（?filter= ?item= で絞り込みと選択）
    // 対象は「お題」か「回答」なので、ID だけでは足りない。キーは {oogiri|answer}-{id}（ADR 0003）
    $routes->post('moderation/(:segment)/resolve', 'ModerationController::resolve/$1');
    $routes->post('moderation/(:segment)/hide', 'ModerationController::hide/$1');
    $routes->post('moderation/(:segment)/delete', 'ModerationController::delete/$1');
    $routes->post('moderation/(:segment)/restrict', 'ModerationController::restrict/$1');

    $routes->get('ng-words', 'NgWordController::index');                 // A-06
    $routes->post('ng-words', 'NgWordController::create');
    $routes->post('ng-words/(:num)', 'NgWordController::update/$1');
    $routes->post('ng-words/(:num)/delete', 'NgWordController::delete/$1');
    $routes->get('ng-words/export', 'NgWordController::export');
    $routes->post('ng-words/import', 'NgWordController::import');

    $routes->get('ads', 'AdController::index');                          // A-07
    $routes->post('ads', 'AdController::create');
    $routes->post('ads/(:num)', 'AdController::update/$1');
    $routes->post('ads/(:num)/delete', 'AdController::delete/$1');

    $routes->get('ogp', 'OgpController::index');                         // A-08
    $routes->post('ogp/regenerate', 'OgpController::regenerate');
    $routes->post('ogp/regenerate-all', 'OgpController::regenerateAll');   // 公開中の画像をすべて作り直す予約
    $routes->post('ogp/template', 'OgpController::updateTemplate');

    $routes->get('inquiries', 'InquiryController::index');               // A-09
    $routes->post('inquiries/(:num)', 'InquiryController::update/$1');

    $routes->get('settings', 'SettingController::index');                // A-10
    $routes->post('settings', 'SettingController::update');
});
