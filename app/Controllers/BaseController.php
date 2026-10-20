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

/**
 * 全 Controller の親
 *
 * Controller の責務は「入力を受け取り、Service を呼び、View かリダイレクトを返す」だけ（CODING_RULES 3章）。
 */
abstract class BaseController extends Controller
{
    /** @var CLIRequest|IncomingRequest */
    protected $request;

    /** @var list<string> */
    protected $helpers = ['url', 'form', 'view'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
    }

    /** 募集URLのスラッグから大喜利を取得する。なければ 404 */
    protected function findOogiriOrFail(string $slug): Oogiri
    {
        try {
            return service('oogiri')->findBySlug($slug);
        } catch (NotFoundException) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * 工程0〜2 の仮画面。工程1・2で各画面の View に置き換える。
     *
     * TODO(inner): 全画面の View ができたら削除する
     */
    protected function placeholder(string $screenId, string $title): string
    {
        return view('placeholder', ['screenId' => $screenId, 'title' => $title]);
    }
}
