<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

/**
 * 管理者へのメール通知（FEATURES 0-8：通報、お問い合わせ、NGワード「要確認」）
 *
 * 宛先は .env の app.adminEmail。未設定なら送らない。急増時はまとめる（通報の S-04 側で判断）。
 * ログにメールアドレスや本文を出さない（CODING_RULES 7章）。
 */
final class MailService
{
    public function notifyAdmin(string $subject, string $body): bool
    {
        $to = (string) env('app.adminEmail', '');

        if ($to === '' || ENVIRONMENT === 'testing') {
            return false;
        }

        try {
            $email = service('email');
            $email->setTo($to);
            $email->setSubject('[ぼけばこ] ' . $subject);
            $email->setMessage($body);

            return $email->send();
        } catch (Throwable $e) {
            log_message('error', '管理者メールの送信に失敗: ' . $e->getMessage());

            return false;
        }
    }
}
