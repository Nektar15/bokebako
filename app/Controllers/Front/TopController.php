<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Services\SettingService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * U-01 トップ・大喜利開催 ／ U-02 開催確認（同じ画面のモーダル）
 */
final class TopController extends BaseController
{
    /** U-01 */
    public function index(): string
    {
        return view('front/u01_top', [
            'templates' => SettingService::ODAI_TEMPLATES,
            'fromSlug'  => (string) $this->request->getGet('from'),
            'odaiMax'   => service('settings')->int('odai_max'),
            'errors'    => session()->getFlashdata('errors') ?? [],
            'old'       => ['odai' => (string) old('odai'), 'passphrase' => (string) old('passphrase'), 'from' => (string) old('from')],
            'ads'       => service('ads')->slots(),
        ]);
    }

    /** U-02「同意して開催する」→ 作成して U-03 へ */
    public function create(): ResponseInterface
    {
        $input = [
            'odai'       => (string) $this->request->getPost('odai'),
            'passphrase' => (string) $this->request->getPost('passphrase'),
            'from'       => (string) $this->request->getPost('from'),
        ];

        try {
            $oogiri = service('oogiri')->create($input);
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->with('errors', $e->errors);
        } catch (AppException $e) {
            return redirect()->back()->withInput()->with('errors', ['_' => $e->getMessage()]);
        }

        return redirect()->to('/o/' . $oogiri->slug . '/created');
    }
}
