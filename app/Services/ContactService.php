<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InquiryType;
use App\Exceptions\ValidationException;

/**
 * お問い合わせ（U-18）
 */
final class ContactService
{
    /**
     * @param array{type?: string, email?: string, url?: string, body?: string} $input
     *
     * @throws ValidationException
     */
    public function send(array $input): void
    {
        $errors = [];
        $type   = InquiryType::tryFrom((string) ($input['type'] ?? ''));
        $email  = trim((string) ($input['email'] ?? ''));
        $url    = trim((string) ($input['url'] ?? ''));
        $body   = trim((string) ($input['body'] ?? ''));

        if ($type === null) {
            $errors['type'] = 'お問い合わせの種類を選んでください';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'メールアドレスを正しく入力してください';
        }
        if ($type !== null && $type->requiresUrl() && $url === '') {
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
