<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * S-05 AI補助判定：キューの投稿を OpenAI へ送り、結果を記録する
 *
 * cron から1分間隔で実行する（README）。Service を呼ぶだけ（CODING_RULES 3章）。
 */
final class JudgeAi extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'ai:judge';
    protected $description = 'S-05 AI補助判定：キューの投稿を OpenAI へ送り、結果を記録する';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        $count = service('moderation')->judgeQueue();
        CLI::write("ai:judge: {$count} 件を判定しました");
    }
}
