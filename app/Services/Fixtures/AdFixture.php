<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\Ad;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-07）用のダミーデータ：広告
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら Seeder に移して削除する。
 */
final class AdFixture
{
    /**
     * @return list<Ad>
     */
    public static function all(): array
    {
        $year = (int) Time::now()->format('Y');
        $rows = [
            // id, name, sponsor, slot, 開始, 終了, status, impressions, clicks
            [1, '秋の新商品キャンペーン', 'まるや製菓', 'top', '10-01', '10-31', 'live', 238410, 2101],
            [2, 'ネタ帳アプリ「ネタノート」', 'ネタノート', 'top', '09-15', '11-15', 'live', 174470, 1200],
            [3, '大喜利の本・フェア', 'コトバ書房', 'bottom', '10-01', '10-20', 'live', 298104, 1612],
            [4, 'お笑いライブ告知', 'スタジオ・ワライ', 'bottom', '10-21', '11-10', 'scheduled', 0, 0],
            [5, '夏のキャンペーン', 'まるや製菓', 'top', '08-01', '08-31', 'ended', 512033, 3890],
            [6, 'テスト配信', 'ぼけばこ運営', 'bottom', '09-01', '09-30', 'stopped', 1204, 3],
        ];

        $items = [];

        foreach ($rows as [$id, $name, $sponsor, $slot, $from, $to, $status, $imp, $clk]) {
            $items[] = new Ad([
                'id'          => $id,
                'name'        => $name,
                'sponsor'     => $sponsor,
                'slot'        => $slot,
                'image'       => 'assets/img/ad-sample.svg',
                'url'         => 'https://example.com/campaign/' . $id,
                'starts_at'   => Time::parse("{$year}-{$from} 00:00:00"),
                'ends_at'     => Time::parse("{$year}-{$to} 23:59:59"),
                'status'      => $status,
                'impressions' => $imp,
                'clicks'      => $clk,
            ]);
        }

        return $items;
    }

    /**
     * 枠ごとの今月の実績（見本の数字）
     *
     * @return array<string, array{live: int, impressions: int, clicks: int}> AdSlot の値 => 実績
     */
    public static function slotSummary(): array
    {
        return [
            'top'    => ['live' => 2, 'impressions' => 412880, 'clicks' => 3301],
            'bottom' => ['live' => 1, 'impressions' => 298104, 'clicks' => 1612],
        ];
    }
}
