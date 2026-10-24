<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Enums\OogiriStatus;
use App\Models\OogiriModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * robots.txt とサイトマップ（SEO。指示書 第2章、ADR 0016）
 *
 * どちらもサイトの URL（app.baseURL）から組み立てる。静的なファイルにすると、Vercel と本番でドメインが違うときに直し忘れる。
 * 管理画面のパスは robots.txt に書かない（推測しにくいパスにしているのに、ここで知らせてしまうため。管理画面は noindex）
 */
final class SeoController extends BaseController
{
    /**
     * サイトマップに載せる結果ページの数（新しい順）
     */
    private const SITEMAP_RESULTS = 1000;

    public function robots(): ResponseInterface
    {
        $lines = [
            'User-agent: *',
            'Disallow: /host/',
            'Disallow: /api/',
            '',
            'Sitemap: ' . site_url('sitemap.xml'),
        ];

        return $this->response->setContentType('text/plain', 'UTF-8')->setBody(implode("\n", $lines) . "\n");
    }

    /**
     * 固定のページと、結果が出た大喜利（結果発表・結果未選出）。募集中は 24 時間で終わるので載せない
     */
    public function sitemap(): ResponseInterface
    {
        $urls = [];

        foreach (['/', 'about', 'guidelines', 'terms', 'privacy', 'contact'] as $path) {
            $urls[] = ['loc' => site_url($path), 'lastmod' => null];
        }
        $rows = (new OogiriModel())->select('slug, decided_at, ends_at')
            ->whereIn('status', [OogiriStatus::Decided->value, OogiriStatus::NoResult->value])
            ->orderBy('ends_at', 'DESC')->findAll(self::SITEMAP_RESULTS);

        foreach ($rows as $row) {
            $urls[] = ['loc' => site_url('o/' . $row->slug), 'lastmod' => ($row->decided_at ?? $row->ends_at)?->format('Y-m-d')];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '<url><loc>' . esc($url['loc']) . '</loc>' . ($url['lastmod'] !== null ? '<lastmod>' . $url['lastmod'] . '</lastmod>' : '') . "</url>\n";
        }

        return $this->response->setContentType('application/xml', 'UTF-8')->setBody($xml . "</urlset>\n");
    }
}
