<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\NgWord;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-06）用のダミーデータ：NGワード
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら Seeder に移して削除する。
 */
final class NgWordFixture
{
    /**
     * カテゴリーの一覧（固定。仕様書に定義がないため見本から。未確定事項に追記済み）
     */
    public const CATEGORIES = ['誹謗中傷', '個人情報', 'いじめ・学校', '脅迫', '性的', 'スパム', 'その他'];

    /**
     * @return list<NgWord>
     */
    public static function all(): array
    {
        $now  = Time::now();
        $rows = [
            // id, word, match_type, category, action, enabled, 更新の何日前
            [1, '殺す', 'partial', '脅迫', 'reject', true, 8],
            [2, '0\d{1,4}-?\d{1,4}-?\d{4}', 'regex', '個人情報', 'reject', true, 11],
            [3, '[\w.+-]+@[\w-]+\.[\w.]+', 'regex', '個人情報', 'reject', true, 11],
            [4, 'LINE', 'partial', '個人情報', 'review', true, 9],
            [5, '担任', 'partial', 'いじめ・学校', 'review', true, 4],
            [6, '住所', 'partial', '個人情報', 'review', true, 11],
            [7, '死ね', 'exact', '誹謗中傷', 'review', true, 11],
            [8, 'http', 'partial', 'スパム', 'review', false, 7],
        ];

        $items = [];

        foreach ($rows as [$id, $word, $match, $category, $action, $enabled, $daysAgo]) {
            $items[] = new NgWord([
                'id'         => $id,
                'word'       => $word,
                'match_type' => $match,
                'category'   => $category,
                'action'     => $action,
                'enabled'    => $enabled,
                'updated_at' => $now->subDays($daysAgo),
            ]);
        }

        return $items;
    }
}
