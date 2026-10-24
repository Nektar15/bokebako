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
 *
 * デプロイ直後は複数のインスタンスが同時に起きる。DevSeeder は最初に全テーブルを空にするので、
 * 2つが同時に流れると、片方が入れた行をもう片方が消してしまう（2026-10-24 に Vercel で起きた）。
 * MySQL 互換の DB では名前付きロック（GET_LOCK）で、準備を1つずつにする。
 */
final class VercelPrepare extends BaseCommand
{
    private const LOCK      = 'bokebako_vercel_prepare';
    private const LOCK_WAIT = 60; // 秒。先に始めたインスタンスの準備（数秒〜十数秒）が終わるのを待つ

    protected $group       = 'bokebako';
    protected $name        = 'vercel:prepare';
    protected $description = 'Vercel の仮置き：共有 DB のマイグレーションと、空のときだけ開発用データを入れる';

    /**
     * @param list<string> $params
     */
    public function run(array $params): void
    {
        $db     = Database::connect();
        $locked = $db->DBDriver === 'MySQLi';

        if ($locked && (int) ($db->query('SELECT GET_LOCK(?, ?) AS ok', [self::LOCK, self::LOCK_WAIT])->getRow()->ok ?? 0) !== 1) {
            CLI::error('vercel:prepare: ほかのインスタンスの準備が終わらないので、今回は何もしません');

            return;
        }

        try {
            service('migrations')->setNamespace('App')->latest();

            if ($db->table('oogiris')->countAllResults() > 0) {
                CLI::write('vercel:prepare: マイグレーションのみ（データあり）');

                return;
            }
            Database::seeder()->call('DevSeeder');
            CLI::write('vercel:prepare: 開発用データを入れました');
        } finally {
            if ($locked) {
                $db->query('SELECT RELEASE_LOCK(?)', [self::LOCK]);
            }
        }
    }
}
