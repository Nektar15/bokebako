<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\Answer;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-04）用のダミーデータ：大喜利をまたいだ回答の一覧
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら Seeder に移して削除する。
 */
final class AdminAnswerFixture
{
    /**
     * A-04 一覧の全行（docs/design/A04-Answers.dc.html の見本どおり）
     *
     * @return list<Answer>
     */
    public static function all(): array
    {
        $now  = Time::now();
        $rows = [
            // id, body, oogiri_id, odai, 何分前, reports, status, ip_hash, browser_id_hash
            [90412, '「袋いりますか？」のあとに、10秒の沈黙を置いてくる', 12480, 'こんなコンビニ店員は嫌だ', 6, 0, 'visible', '3fa1…9c0e', 'b72d…41af'],
            [90411, '深夜になると、急に関西弁になる', 12480, 'こんなコンビニ店員は嫌だ', 22, 0, 'visible', '91c4…2b7d', '0e5a…c3d1'],
            [90398, '2組の〇〇がやりそうなこと全部', 12471, 'うちの担任の嫌なところ選手権', 258, 5, 'hidden', 'aa02…7f13', '5d9e…0b42'],
            [90377, 'LINEのIDはこちら→', 12479, '100年後の運動会あるある', 289, 1, 'hidden', 'c810…e5a2', '77f0…19be'],
            [90360, '玉入れの玉が全部ドローン', 12479, '100年後の運動会あるある', 315, 0, 'visible', '4b6e…d091', 'e2c8…a774'],
            [90341, 'このお題、主催者のセンス0点', 12466, '絶対に流行らない新しい挨拶', 400, 2, 'visible', '19dd…6a3f', '8c13…f0e9'],
            [90322, '「おはようございました」', 12466, '絶対に流行らない新しい挨拶', 488, 0, 'visible', '6f2a…31c8', 'd4b7…5e20'],
            [90301, '（同じ文面の連続投稿）', 12460, 'ロボットが絶対に言わない一言', 572, 0, 'deleted', 'aa02…7f13', '5d9e…0b42'],
            [90288, '「充電させてください」と言って、人に寄りかかる', 12460, 'ロボットが絶対に言わない一言', 660, 0, 'visible', 'e910…0c55', '3a6b…92dd'],
        ];

        $items = [];

        foreach ($rows as [$id, $body, $oogiriId, $odai, $minutesAgo, $reports, $status, $ip, $bid]) {
            $items[] = new Answer([
                'id'              => $id,
                'oogiri_id'       => $oogiriId,
                'odai'            => $odai,
                'body'            => $body,
                'status'          => $status,
                'rank'            => null,
                'is_favorite'     => false,
                'is_mine'         => false,
                'report_count'    => $reports,
                'ip_hash'         => $ip,
                'browser_id_hash' => $bid,
                'created_at'      => $now->subMinutes($minutesAgo),
            ]);
        }

        return $items;
    }
}
