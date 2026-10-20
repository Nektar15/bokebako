<?php

declare(strict_types=1);

/*
 * Vercel 仮置き用の入口（ADR 0008）。本番（Xserver）では使わない。本番の入口は public/index.php
 *
 * Vercel の PHP ランタイム（vercel-php）は、静的ファイルにないリクエストをすべてこのファイルに送る。
 * ここでは Vercel 固有の準備だけをして、本来の入口 public/index.php に渡す。
 *
 * 1. 書き込み先を /tmp にする（プロジェクトのフォルダは読み取り専用）。環境変数 BOKEBAKO_WRITABLE → Config\Paths
 * 2. 利用者の IP を Vercel のヘッダーから取る（PHP から見える接続元は 127.0.0.1 のため。レート制限が全員共通にならないように）
 * 3. DB（SQLite、/tmp）がなければ、マイグレーションと開発用データを入れる。/tmp は数分〜数時間で消えるので、そのたびに入れ直しになる
 * 4. cron の代わりに、1分に1回まで、リクエストのついでに cron:minute（S-01・S-02・S-04・S-05）を動かす
 *
 * 環境変数（Vercel の Project Settings → Environment Variables。名前の「.」は「_」にする）は docs/vercel.md
 */

// Vercel の環境変数名には「.」が使えないので、下線で登録して CodeIgniter の名前に読み替える
// 例：database_default_database → database.default.database、app_hashSecret → app.hashSecret
$childEnv = getenv(); // spark に渡す環境変数（getenv() の一覧には putenv() で足したものが入らないので、ここに足していく）

foreach (getenv() as $name => $value) {
    if (preg_match('/\A(app|database|cookie|devSeeder|ogp|captcha|openai|mail)_[A-Za-z_]+\z/', $name) === 1) {
        $dotted = str_replace('_', '.', $name);
        putenv($dotted . '=' . $value);
        $_ENV[$dotted] = $_SERVER[$dotted] = $childEnv[$dotted] = $value;
    }
}

$root     = dirname(__DIR__);

// 静的ファイル（public/ の CSS・JS・画像）がこちらに来たら、内蔵サーバーにそのまま返させる（VERCEL_PHP_DOCROOT=public のとき）
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($path) && $path !== '/' && ! str_ends_with($path, '.php') && is_file($root . '/public' . $path)) {
    return false;
}

$writable = getenv('BOKEBAKO_WRITABLE') ?: '/tmp/bokebako';
putenv('BOKEBAKO_WRITABLE=' . $writable);
$childEnv['BOKEBAKO_WRITABLE'] = $writable;

foreach (['cache', 'logs', 'session', 'uploads', 'debugbar', 'ogp-templates', 'fonts'] as $dir) {
    if (! is_dir($writable . '/' . $dir)) {
        mkdir($writable . '/' . $dir, 0775, true);
    }
}

// 2. 利用者の IP（Vercel が付ける x-real-ip。利用者が送ったものは Vercel が上書きする）
if (isset($_SERVER['HTTP_X_REAL_IP']) && filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP) !== false) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_REAL_IP'];
}

// 公開 URL は届いたホスト名から作る（プレビューの URL ごとに設定し直さなくてよいように）
if (getenv('app.baseURL') === false && isset($_SERVER['HTTP_HOST']) && preg_match('/\A[a-z0-9.-]+\.vercel\.app\z/i', $_SERVER['HTTP_HOST']) === 1) {
    putenv('app.baseURL=https://' . $_SERVER['HTTP_HOST'] . '/');
    $childEnv['app.baseURL'] = 'https://' . $_SERVER['HTTP_HOST'] . '/';
}

/**
 * spark コマンドを同じ PHP で動かす（失敗しても画面は続ける。ログに残す）
 *
 * シェルを通すと「.」を含む環境変数（app.hashSecret など）が落ちるので、proc_open に環境変数を直接渡す
 */
$spark = static function (string ...$args) use ($root, $writable, $childEnv): void {
    $php  = PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $proc = proc_open([$php, 'spark', ...$args], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $childEnv);

    if ($proc === false) {
        return;
    }
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    if ($code !== 0) {
        file_put_contents($writable . '/logs/vercel-setup.log', date('c') . ' spark ' . implode(' ', $args) . " => {$code}\n{$output}\n", FILE_APPEND);
    }
};

$lock = fopen($writable . '/.setup.lock', 'c');

if ($lock !== false && flock($lock, LOCK_EX)) {
    // 3. DB の準備（このインスタンスで初めてのリクエストのとき）
    $database = $childEnv['database.default.database'] ?? '';

    if ($database !== '' && ! is_file($database)) {
        $spark('migrate', '--all', '-n', 'App');
        $spark('db:seed', 'DevSeeder');
    }

    // 4. cron の代わり（1分に1回まで）
    $marker = $writable . '/.cron-minute';

    if (! is_file($marker) || time() - (int) filemtime($marker) >= 60) {
        touch($marker);
        $spark('cron:minute');
    }
    flock($lock, LOCK_UN);
}

require $root . '/public/index.php';
