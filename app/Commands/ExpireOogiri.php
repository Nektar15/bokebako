<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * S-02 72時間結果待ち処理：選出期限を過ぎた大喜利を結果未選出で確定する
 *
 * cron から1分間隔で実行する（README）。Service を呼ぶだけ（CODING_RULES 3章）。
 */
final class ExpireOogiri extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'oogiri:expire';
    protected $description = 'S-02 72時間結果待ち処理：選出期限を過ぎた大喜利を結果未選出で確定する';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        $count = service('lifecycle')->expireUnselected();
        CLI::write("oogiri:expire: {$count} 件を結果未選出で確定しました");
    }
}
