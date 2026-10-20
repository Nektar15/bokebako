<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * S-07 OGP画像生成：欠けている募集用・結果用の画像を作る
 *
 * cron から1分間隔で実行する（README）。Service を呼ぶだけ（CODING_RULES 3章）。
 */
final class GenerateOgp extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'ogp:generate';
    protected $description = 'S-07 OGP画像生成：欠けている募集用・結果用の画像を作る';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        $count = service('lifecycle')->generateMissingOgp();
        CLI::write("ogp:generate: {$count} 件の画像を生成しました");
    }
}
