<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Entities\Oogiri;
use App\Exceptions\NotFoundException;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 全 Controller の親
 *
 * Controller の責務は「入力を受け取り、Service を呼び、View かリダイレクトを返す」だけ（CODING_RULES 3章）。
 */
abstract class BaseController extends Controller
{
    /**
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * @var list<string>
     */
    protected $helpers = ['url', 'form', 'view'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * ページキャッシュに入れる。末尾が「/」の URL は入れない（キャッシュのキーは届いた URL そのものなので、
     * 「/o/abc/」のような別の書き方で入れると、非表示・確定のときに捨てられず古いページが残る。監査 2026-10-21）
     */
    protected function cachePage(int $time): void
    {
        $path = $this->request->getUri()->getPath();

        if ($path !== '/' && str_ends_with($path, '/')) {
            return;
        }
        parent::cachePage($time);
    }

    /**
     * 入力エラーで戻すとき（withInput）に、パスワードをセッションへ残さない（平文がセッションファイルに残るため。監査 2026-10-21）
     */
    protected function forgetSecrets(): void
    {
        foreach (['password', 'current_password', 'new_password', 'password_confirm'] as $name) {
            unset($_POST[$name]);
        }
    }

    /**
     * JSON API の本文（JSON かフォーム）。壊れた JSON は空、値は文字列か数値だけにする（配列・入れ子で 500 にしない。監査 2026-10-21）
     *
     * @return array<string, int|string>
     */
    protected function apiBody(): array
    {
        try {
            $json = $this->request->getJSON(true);
        } catch (Throwable) {
            $json = null;
        }
        $body = is_array($json) ? $json : (array) $this->request->getPost();
        $out  = [];

        foreach ($body as $key => $value) {
            if (is_string($key) && (is_string($value) || is_int($value))) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * 募集URLのスラッグから大喜利を取得する。なければ 404
     */
    protected function findOogiriOrFail(string $slug): Oogiri
    {
        try {
            return service('oogiri')->findBySlug($slug);
        } catch (NotFoundException) {
            throw PageNotFoundException::forPageNotFound();
        }
    }
}
