<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\URI;

/**
 * ページキャッシュの生成と破棄（SPEC 4.1、FEATURES 0-6）
 *
 * 結果発表（U-10）・募集終了（U-11）・結果未選出（U-12）・紹介・規約類は Controller が cachePage() で静的 HTML にする。
 * 状態が変わったときにここで捨てる。キーは CodeIgniter の ResponseCache と同じ計算（GET:＋クエリなしの URL の md5）。
 * ResponseCache は page クエリだけをキーに含めるが（Config\Cache）、?page= 付きは保存しないので、捨てるのはクエリなしの1件でよい。
 */
final class PageCacheService
{
    /**
     * 結果・終了ページのキャッシュ秒数。状態変化で捨てるので長めでよい
     */
    public const TTL_RESULT = 86400;

    /**
     * 紹介・規約類
     */
    public const TTL_STATIC = 3600;

    /**
     * 大喜利に関係するページ（募集URL・回答一覧・シェア用）を捨てる
     */
    public function purgeOogiri(string $slug): void
    {
        $this->purge('o/' . $slug);
        $this->purge('o/' . $slug . '/answers');
    }

    /**
     * 1ページ分を捨てる（path は site_url() に渡す形）
     */
    public function purge(string $path): void
    {
        cache()->delete(self::key($path));
    }

    public static function key(string $path): string
    {
        $uri = new URI(site_url($path));

        return md5('GET:' . $uri->setFragment('')->setQuery(''));
    }
}
