<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;

/**
 * お問い合わせ（U-18）
 */
final class ContactService
{
    public const TYPES = [
        'general'   => '一般問い合わせ',
        'delete'    => '投稿削除依頼',
        'rights'    => '権利侵害',
        'ad'        => '広告掲載について',
        'other'     => 'その他',
    ];

    /** 対象URLが必須になる種別 */
    public const URL_REQUIRED_TYPES = ['delete', 'rights'];

    /**
     * @param array{type?: string, email?: string, url?: string, body?: string} $input
     *
     * @throws ValidationException
     */
    public function send(array $input): void
    {
        $errors = [];
        $type   = (string) ($input['type'] ?? '');
        $email  = trim((string) ($input['email'] ?? ''));
        $url    = trim((string) ($input['url'] ?? ''));
        $body   = trim((string) ($input['body'] ?? ''));

        if (! isset(self::TYPES[$type])) {
            $errors['type'] = 'お問い合わせの種類を選んでください';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'メールアドレスを正しく入力してください';
        }
        if (in_array($type, self::URL_REQUIRED_TYPES, true) && $url === '') {
            $errors['url'] = '対象のURLを入力してください';
        }
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) === false) {
            $errors['url'] = 'URLを正しく入力してください';
        }
        if ($body === '') {
            $errors['body'] = 'お問い合わせ内容を入力してください';
        } elseif (mb_strlen($body) > 2000) {
            $errors['body'] = 'お問い合わせ内容は2000文字以内で入力してください';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // TODO(inner): CAPTCHA の検証、inquiries への保存、管理者へのメール通知
    }
}
