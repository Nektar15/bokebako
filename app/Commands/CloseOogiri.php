<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * S-01 24時間終了処理：ends_at を過ぎた募集中の大喜利を結果選択待ちにする
 *
 * cron から1分間隔で実行する（README）。Service を呼ぶだけ（CODING_RULES 3章）。
 */
final class CloseOogiri extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'oogiri:close';
    protected $description = 'S-01 24時間終了処理：ends_at を過ぎた募集中の大喜利を結果選択待ちにする';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        $count = service('lifecycle')->closeEnded();
        CLI::write("oogiri:close: {$count} 件を結果選択待ちにしました");
    }
}
