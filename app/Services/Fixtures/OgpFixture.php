<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\OgpJob;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-08）用のダミーデータ：OGP 画像の生成記録
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら Seeder に移して削除する。
 */
final class OgpFixture
{
    /**
     * 最近の生成（新しい順）
     *
     * @return list<OgpJob>
     */
    public static function recentJobs(): array
    {
        $now  = Time::now();
        $rows = [
            // id, kind, target, target_id, actor, succeeded, 何分前
            [1, 'recruit', '#12480 こんなコンビニ店員は嫌だ', 12480, '自動', true, 5],
            [2, 'answer', 'A-90412', 90412, '自動', true, 4],
            [3, 'result', '#12452 絶対に売れない駄菓子の商品名とは？', 12452, '自動', true, 219],
            [4, 'result', '#12441 こんな結婚式の余興は嫌だ', 12441, 'dev-admin', true, 248],
            [5, 'answer', 'A-90122', 90122, '自動', false, 797],
            [6, 'recruit', '#12441 こんな結婚式の余興は嫌だ', 12441, '自動', true, 1350],
        ];

        $items = [];

        foreach ($rows as [$id, $kind, $target, $targetId, $actor, $ok, $minutesAgo]) {
            $items[] = new OgpJob([
                'id'         => $id,
                'kind'       => $kind,
                'target'     => $target,
                'target_id'  => $targetId,
                'actor'      => $actor,
                'succeeded'  => $ok,
                'created_at' => $now->subMinutes($minutesAgo),
            ]);
        }

        return $items;
    }
}
