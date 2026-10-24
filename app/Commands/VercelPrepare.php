<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Vercel の仮置きで、全インスタンスが共有する外部 DB を準備する（ADR 0015。本番では使わない）
 *
 * マイグレーションを最新にし、DB が空のとき（最初の1回）だけ開発用データ（DevSeeder）を入れる。
 * api/index.php が、インスタンスが起きて最初のリクエストのときに呼ぶ。何度呼んでもよい。
 */
final class VercelPrepare extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'vercel:prepare';
    protected $description = 'Vercel の仮置き：共有 DB のマイグレーションと、空のときだけ開発用データを入れる';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        service('migrations')->setNamespace('App')->latest();

        if (Database::connect()->table('oogiris')->countAllResults() > 0) {
            CLI::write('vercel:prepare: マイグレーションのみ（データあり）');

            return;
        }
        Database::seeder()->call('DevSeeder');
        CLI::write('vercel:prepare: 開発用データを入れました');
    }
}
