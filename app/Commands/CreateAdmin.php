<?php

declare(strict_types=1);

namespace App\Commands;

use App\Enums\AdminRole;
use App\Exceptions\ValidationException;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * A-01 管理者アカウントを追加する（最初の上位管理者を作るため。2人目からは管理画面の「管理者アカウント」でも追加できる）
 *
 * 例：php spark admin:create sakai 坂井 --role super
 * パスワードは対話で入力する（コマンドの履歴に残さないため、引数では受け取らない）。
 */
final class CreateAdmin extends BaseCommand
{
    protected $group       = 'bokebako';
    protected $name        = 'admin:create';
    protected $description = 'A-01 管理者アカウントを追加する（最初の上位管理者の作成用）';
    protected $usage       = 'admin:create <ID> <表示名> [--role super|general]';
    protected $arguments   = ['ID' => 'ログイン ID（メールアドレスも可）', '表示名' => '操作履歴に出る名前'];
    protected $options     = ['--role' => 'super（上位管理者・既定）または general（一般管理者）'];

    /**
     * @param list<string> $params
     */
    public function run(array $params): int
    {
        $loginId  = $params[0] ?? CLI::prompt('ID');
        $name     = $params[1] ?? CLI::prompt('表示名');
        $role     = (string) (CLI::getOption('role') ?? AdminRole::Super->value);
        $password = $this->askPassword();

        if ($password === null) {
            CLI::error('パスワードが一致しません');

            return EXIT_ERROR;
        }

        try {
            $account = service('adminAccount')->createFromCommand(['login_id' => $loginId, 'name' => $name, 'role' => $role, 'password' => $password]);
        } catch (ValidationException $e) {
            foreach ($e->errors as $message) {
                CLI::error($message);
            }

            return EXIT_ERROR;
        }
        CLI::write("admin:create: {$account->login_id}（{$account->role()->label()}）を追加しました", 'green');

        return EXIT_SUCCESS;
    }

    /**
     * パスワードを画面に出さずに2回入力させる（端末が対応していなければ表示される旨を出す）。一致しなければ null
     */
    private function askPassword(): ?string
    {
        $hidden = DIRECTORY_SEPARATOR === '/' && stream_isatty(STDIN);

        if (! $hidden) {
            CLI::write('この端末では入力したパスワードが表示されます', 'yellow');
        }
        $first  = $this->readSecret('パスワード', $hidden);
        $second = $this->readSecret('パスワード（確認）', $hidden);

        return $first === $second ? $first : null;
    }

    private function readSecret(string $label, bool $hidden): string
    {
        CLI::print($label . ': ');

        if ($hidden) {
            shell_exec('stty -echo');
        }
        $value = rtrim((string) fgets(STDIN), "\r\n");

        if ($hidden) {
            shell_exec('stty echo');
            CLI::newLine();
        }

        return $value;
    }
}
