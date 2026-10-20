<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Libraries\Captcha;
use App\Models\InquiryModel;

/**
 * お問い合わせ（U-18）：受け付けて保存し、管理者へメールで知らせる
 */
final class ContactService
{
    public function __construct(
        private readonly InquiryModel $inquiries,
        private readonly RateLimitService $rateLimit,
        private readonly Captcha $captcha,
    ) {
    }

    /**
     * @param array{type?: string, email?: string, url?: string, body?: string, captcha?: string} $input
     *
     * @throws AppException        レート制限
     * @throws ValidationException
     */
    public function send(array $input): void
    {
        $this->rateLimit->assertAllowed(RateLimitService::ACTION_CONTACT);
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

        if ($errors === [] && ! $this->captcha->verify((string) ($input['captcha'] ?? ''))) {
            $errors['_'] = 'CAPTCHA の確認に失敗しました。もう一度お試しください';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        $id = $this->inquiries->insert([
            'type'   => $type?->value,
            'email'  => $email,
            'url'    => $url,
            'body'   => $body,
            'status' => InquiryStatus::Todo->value,
        ]);
        service('mail')->notifyAdmin('お問い合わせ（' . $type?->label() . '）', "お問い合わせ #{$id} を受け付けました。\n管理画面の「お問い合わせ管理」で確認してください。");
    }
}
