<?php

declare(strict_types=1);

/*
 * staging 環境の起動設定。本番と同じ扱い（エラーは表示しない）。
 * 違いは adminAuth Filter の仮認証（ADMIN_ALLOWED_IPS からのみ許可）だけ。
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

defined('CI_DEBUG') || define('CI_DEBUG', false);
