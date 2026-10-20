<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\AdminLog;
use App\Entities\Oogiri;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-03）用のダミーデータ：大喜利の一覧と操作履歴
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら Seeder に移して削除する。
 */
final class AdminOogiriFixture
{
    /**
     * A-03 一覧の全行（docs/design/A03-Oogiri.dc.html の見本どおり）
     *
     * @return list<Oogiri>
     */
    public static function all(): array
    {
        $now  = Time::now();
        $rows = [
            // id, odai, 作成の何時間前, status, pv, answers, shares, derived, reports, slug
            [12480, 'こんなコンビニ店員は嫌だ', 5, 'open', 1284, 47, 63, 2, 0, 'k7Qm2xPz'],
            [12479, '100年後の運動会あるある', 6, 'open', 412, 12, 9, 0, 0, 'Hd3kLp9a'],
            [12471, 'うちの担任の嫌なところ選手権', 9, 'hidden', 233, 8, 4, 0, 5, 'Wq8nRt2v'],
            [12466, '絶対に流行らない新しい挨拶', 28, 'awaiting_selection', 3911, 154, 201, 12, 0, 'Zp3cW9aQ'],
            [12460, 'ロボットが絶対に言わない一言', 31, 'awaiting_selection', 806, 33, 25, 1, 1, 'Mn6bXs4e'],
            [12452, '絶対に売れない駄菓子の商品名とは？', 38, 'decided', 15204, 412, 688, 38, 0, 'q8Lr4nTb'],
            [12441, 'こんな結婚式の余興は嫌だ', 50, 'decided', 7740, 221, 302, 19, 2, 'Cv5tYu7i'],
            [12398, '宇宙人が地球で驚いたこと', 110, 'no_result', 610, 38, 20, 1, 0, 'Bx2gHj8k'],
            [12377, '（削除済みのお題）', 130, 'deleted', 98, 3, 0, 0, 7, 'Ty9pQw1z'],
        ];

        $items = [];

        foreach ($rows as [$id, $odai, $hoursAgo, $status, $pv, $answers, $shares, $derived, $reports, $slug]) {
            $created = $now->subHours($hoursAgo);
            $ends    = $created->addHours(24);
            $items[] = new Oogiri([
                'id'                 => $id,
                'slug'               => $slug,
                'odai'               => $odai,
                'status'             => $status,
                'created_at'         => $created,
                'starts_at'          => $created,
                'ends_at'            => $ends,
                'selection_deadline' => $status === 'awaiting_selection' ? $ends->addHours(72) : null,
                'decided_at'         => $status === 'decided' ? $ends->addHours(6)->addMinutes(37) : null,
                'pv'                 => $pv,
                'answer_count'       => $answers,
                'share_count'        => $shares,
                'derived_count'      => $derived,
                'report_count'       => $reports,
                'parent_oogiri_id'   => $id === 12452 ? 12301 : null,
                'fixable'            => false,
            ]);
        }

        return $items;
    }

    /**
     * 状態ごとの件数（一覧のタブに出す。見本の数字）
     *
     * @return array<string, int> 状態の値（'all' を含む） => 件数
     */
    public static function counts(): array
    {
        return [
            'all'                => 12480,
            'open'               => 214,
            'awaiting_selection' => 386,
            'decided'            => 10902,
            'no_result'          => 920,
            'hidden'             => 41,
            'deleted'            => 17,
        ];
    }

    /**
     * 詳細画面の操作履歴（新しい順）
     *
     * @return list<AdminLog>
     */
    public static function logs(Oogiri $oogiri): array
    {
        $created = $oogiri->created_at;
        $rows    = [['開催', '開催者', $created]];

        if (! $oogiri->isOpen()) {
            $rows[] = ['募集終了 → 結果選択待ち', '自動', $oogiri->ends_at->addMinutes(1)];
        }
        if ($oogiri->decided_at !== null) {
            $rows[] = ['結果を確定（3件）', '開催者', $oogiri->decided_at];
            $rows[] = ['結果用OGP画像を生成', '自動', $oogiri->decided_at->addMinutes(1)];
        }
        if ($oogiri->status()->value === 'hidden') {
            $rows[] = ['通報が条件に達したため一時非表示', '自動', $created->addMinutes(18)];
        }

        $logs = [];

        foreach (array_reverse($rows) as $i => [$what, $actor, $at]) {
            $logs[] = new AdminLog(['id' => $i + 1, 'what' => $what, 'actor' => $actor, 'created_at' => $at]);
        }

        return $logs;
    }
}
