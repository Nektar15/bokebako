<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * S-04 通報処理：条件に達した投稿を一時非表示にし、管理者へ通知する
 *
 * cron から1分間隔で実行する（README）。Service を呼ぶだけ（CODING_RULES 3章）。
 */
final class ProcessReports extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'reports:process';
    protected $description = 'S-04 通報処理：条件に達した投稿を一時非表示にし、管理者へ通知する';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        $count = service('moderation')->processReports();
        CLI::write("reports:process: {$count} 件を一時非表示にしました");
    }
}
