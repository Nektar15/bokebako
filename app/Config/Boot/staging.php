<?php

declare(strict_types=1);

/*
 * staging 環境の起動設定。本番と同じ扱い（エラーは表示しない）。
 * 管理画面の IP 制限（ADMIN_ALLOWED_IPS）は環境を問わず .env で決める。
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

defined('CI_DEBUG') || define('CI_DEBUG', false);
