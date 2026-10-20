<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 1分間隔の自動処理をまとめて実行する（S-01・S-02・S-04・S-05）。Xserver の cron にはこれ1本を登録する（README）
 */
final class CronMinute extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'cron:minute';
    protected $description = '1分間隔の自動処理（oogiri:close → oogiri:expire → reports:process → ai:judge）をまとめて実行する';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        foreach (['oogiri:close', 'oogiri:expire', 'reports:process', 'ai:judge'] as $command) {
            $this->call($command);
        }
        CLI::write('cron:minute: done');
    }
}
