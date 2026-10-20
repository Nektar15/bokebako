<?php

declare(strict_types=1);

/**
 * View で使ってよい表示用ヘルパー（CODING_RULES 8-2）
 *
 * ここに定義したもの以外の PHP を View で呼ばない。
 */

if (! function_exists('asset_url')) {
    /**
     * 静的資産のURL（バージョン付き。長期ブラウザキャッシュ用：SPEC 4.1）
     *
     * バージョンは .env の app.assetVersion。未設定ならファイルの更新時刻を使う。
     */
    function asset_url(string $path): string
    {
        $path    = ltrim($path, '/');
        $version = (string) env('app.assetVersion', '');

        if ($version === '') {
            $file    = FCPATH . 'assets/' . $path;
            $version = is_file($file) ? (string) filemtime($file) : '0';
        }

        return base_url('assets/' . $path) . '?v=' . rawurlencode($version);
    }
}

if (! function_exists('part')) {
    /**
     * 部品（partials/）に値を渡して描画する
     *
     * view() は渡した値をリクエスト内で保持し、あとの部品にも見えてしまう（saveData）。
     * 部品ごとに値を閉じるため、必ずこの関数を使う。
     *
     * @param array<string, mixed> $data
     */
    function part(string $name, array $data = []): string
    {
        return view($name, $data, ['saveData' => false]);
    }
}

if (! function_exists('admin_url')) {
    /** 管理画面のURL。パスの先頭は .env の app.adminPath */
    function admin_url(string $path = ''): string
    {
        $base = trim((string) env('app.adminPath', 'admin'), '/');

        return site_url($path === '' ? $base : $base . '/' . ltrim($path, '/'));
    }
}

if (! function_exists('nl2br_esc')) {
    /** 改行を保ったまま安全に出力する（回答本文など） */
    function nl2br_esc(string $text): string
    {
        return nl2br(esc($text));
    }
}
