<?php

declare(strict_types=1);

namespace App\Services;

use App\Filters\BrowserId;
use CodeIgniter\HTTP\IncomingRequest;
use RuntimeException;

/**
 * 投稿元の識別（FEATURES 0-2）：匿名ブラウザID・IP・開催者トークンのハッシュ化と発行
 *
 * 生の値は保存しない（CODING_RULES 6章・7章）。ハッシュは .env の app.hashSecret を鍵にした hash_hmac sha256。
 */
final class IdentityService
{
    public const HOST_COOKIE          = 'bk_host';         // 開催者トークン（slug ごと）。localStorage と併用（U-03）
    public const HOST_COOKIE_LIFETIME = 60 * 60 * 24 * 365; // 1年（ADR 0004-3）

    private string $secret;

    public function __construct()
    {
        $this->secret = (string) env('app.hashSecret', '');

        if (strlen($this->secret) < 32 && ENVIRONMENT === 'production') {
            throw new RuntimeException('app.hashSecret を .env に設定してください（32文字以上）');
        }
        if ($this->secret === '') {
            $this->secret = 'development-only-secret-do-not-use-in-production';
        }
    }

    /**
     * 任意の値のハッシュ（64文字）
     */
    public function hash(string $value): string
    {
        return hash_hmac('sha256', $value, $this->secret);
    }

    /**
     * 現在のリクエストの匿名ブラウザIDのハッシュ。Cookie がなければ空（Filter が発行する前の CLI など）
     */
    public function browserIdHash(): string
    {
        $request = service('request');
        $id      = $request instanceof IncomingRequest ? (string) $request->getCookie(BrowserId::COOKIE_NAME) : '';

        return $id === '' ? '' : $this->hash($id);
    }

    /**
     * 現在のリクエストの IP のハッシュ
     */
    public function ipHash(): string
    {
        return $this->hash(service('request')->getIPAddress());
    }

    /**
     * 開催者トークンを発行する（推測困難。DB にはハッシュだけを保存する）
     *
     * @return array{token: string, hash: string}
     */
    public function issueHostToken(): array
    {
        $token = bin2hex(random_bytes(24));

        return ['token' => $token, 'hash' => $this->hash($token)];
    }

    /**
     * 推測困難なスラッグ（8文字、URL に使える文字だけ）
     */
    public function newSlug(): string
    {
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 紛らわしい l / o / 0 / 1 / I / O を除く
        $slug     = '';

        for ($i = 0; $i < 8; $i++) {
            $slug .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $slug;
    }

    /**
     * ブラウザが持っている開催者トークン（Cookie）。なければ空
     */
    public function hostTokenFromRequest(string $slug): string
    {
        $request = service('request');

        if (! $request instanceof IncomingRequest) {
            return '';
        }
        // JS は X-Host-Token ヘッダーでも送れる（localStorage 併用。U-08）
        $header = $request->header('X-Host-Token');
        if ($header !== null && $header->getValue() !== '') {
            return (string) $header->getValue();
        }

        return (string) $request->getCookie(self::HOST_COOKIE . '_' . $slug);
    }

    /**
     * 開催者トークンを Cookie に保存する（U-03・合言葉での復旧）
     */
    public function rememberHostToken(string $slug, string $token): void
    {
        service('response')->setCookie(self::HOST_COOKIE . '_' . $slug, $token, self::HOST_COOKIE_LIFETIME, '', '/', '', null, true, 'Lax');
    }
}
