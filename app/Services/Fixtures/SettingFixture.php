<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\AdminLog;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-10）用のダミーデータ：設定の変更履歴
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら削除する。
 */
final class SettingFixture
{
    /**
     * 変更履歴（新しい順）
     *
     * @return list<AdminLog>
     */
    public static function history(): array
    {
        $now  = Time::now();
        $rows = [
            // 何日前, 誰, 内容
            [5, 'dev-admin', '連続投稿の判定回数 5 → 3'],
            [9, 'dev-admin', 'AI補助判定の上限 5,000 → 10,000'],
            [12, 'dev-admin', '初期設定'],
        ];

        $logs = [];

        foreach ($rows as $i => [$daysAgo, $actor, $what]) {
            $logs[] = new AdminLog(['id' => $i + 1, 'actor' => $actor, 'what' => $what, 'created_at' => $now->subDays($daysAgo)]);
        }

        return $logs;
    }
}
