<?php

declare(strict_types=1);

namespace App\Libraries;

use Throwable;

/**
 * CAPTCHA の検証（FEATURES 0-3：レート制限時・お問い合わせ時）
 *
 * サービスは Cloudflare Turnstile を仮採用（未確定事項3、ADR 0004）。.env の captcha.siteKey / captcha.secretKey が空なら
 * 検証を行わず通す（開発・ステージングで使えるように）。
 */
final class Captcha
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    public const FIELD      = 'cf-turnstile-response';

    public function siteKey(): string
    {
        return (string) env('captcha.siteKey', '');
    }

    public function isEnabled(): bool
    {
        return $this->siteKey() !== '' && (string) env('captcha.secretKey', '') !== '';
    }

    /**
     * 応答トークンを検証する。無効なら false
     */
    public function verify(string $token): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }
        if ($token === '') {
            return false;
        }

        try {
            $response = service('curlrequest', ['timeout' => 10])->post(self::VERIFY_URL, [
                'form_params' => ['secret' => (string) env('captcha.secretKey', ''), 'response' => $token, 'remoteip' => service('request')->getIPAddress()],
                'http_errors' => false,
            ]);
            $data = json_decode((string) $response->getBody(), true);

            return is_array($data) && ($data['success'] ?? false) === true;
        } catch (Throwable $e) {
            log_message('error', 'CAPTCHA の検証に失敗: ' . $e->getMessage());

            return false;
        }
    }
}
