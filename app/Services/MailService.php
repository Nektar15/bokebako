<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

/**
 * 管理者へのメール通知（FEATURES 0-8：通報、お問い合わせ、NGワード「要確認」）
 *
 * 宛先は .env の app.adminEmail。未設定なら送らない。急増時はまとめる（通報の S-04 側で判断）。
 * 文面は app/Language/ja/Mail.php のテンプレート。
 * ログにメールアドレスや本文を出さない（CODING_RULES 7章）。
 */
final class MailService
{
    /**
     * テンプレートで管理者へ通知する
     *
     * @param string                    $template Mail.php のキー（report / autoHidden / contact / ngReview）
     * @param array<string, int|string> $values   差し込む値（{name}）
     */
    public function notifyAdmin(string $template, array $values = []): bool
    {
        $message = $this->compose($template, $values);

        return $this->send($message['subject'], $message['body']);
    }

    /**
     * テンプレートから件名と本文を作る（送らない。テスト・確認用）
     *
     * @param array<string, int|string> $values
     *
     * @return array{subject: string, body: string}
     */
    public function compose(string $template, array $values = []): array
    {
        $values += ['adminUrl' => admin_url(), 'url' => admin_url()];

        return [
            'subject' => lang('Mail.subjectPrefix') . self::fill(lang('Mail.' . $template . '.subject'), $values),
            'body'    => self::fill(lang('Mail.' . $template . '.body'), $values) . self::fill(lang('Mail.footer'), $values),
        ];
    }

    /**
     * {name} を値に置き換える（ICU の書式は使わない。本文に {} を含む値が来ても崩れないように）
     *
     * @param array<string, int|string> $values
     */
    private static function fill(string $text, array $values): string
    {
        return strtr($text, array_combine(array_map(static fn ($k) => '{' . $k . '}', array_keys($values)), array_map('strval', array_values($values))));
    }

    private function send(string $subject, string $body): bool
    {
        $to = (string) env('app.adminEmail', '');

        if ($to === '' || ENVIRONMENT === 'testing') {
            return false;
        }

        try {
            $email = service('email');
            $email->setTo($to);
            $email->setSubject($subject);
            $email->setMessage($body);

            return $email->send();
        } catch (Throwable $e) {
            log_message('error', '管理者メールの送信に失敗: ' . $e->getMessage());

            return false;
        }
    }
}
