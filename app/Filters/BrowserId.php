<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 匿名ブラウザID（FEATURES 0-2）
 *
 * 初回アクセス時に推測困難な値を発行し、長期 Cookie に保存する。
 * DB に保存するときは BrowserIdService でハッシュ化する（生の値は保存しない）。
 */
class BrowserId implements FilterInterface
{
    public const COOKIE_NAME = 'bk_bid';
    public const LIFETIME    = 60 * 60 * 24 * 365; // 1年

    public function before(RequestInterface $request, $arguments = null)
    {
        // getCookie() は IncomingRequest にしかない（CLI など Cookie を持たないリクエストは対象外）
        if (! $request instanceof IncomingRequest || $request->getCookie(self::COOKIE_NAME) !== null) {
            return null;
        }

        $id = bin2hex(random_bytes(16));
        service('response')->setCookie(self::COOKIE_NAME, $id, self::LIFETIME, '', '/', '', null, true, 'Lax');
        // 同じリクエスト内でも読めるように、リクエスト側にも持たせる（Cookie の発行は Filter の責務なのでここだけ許可）
        $_COOKIE[self::COOKIE_NAME] = $id;

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
