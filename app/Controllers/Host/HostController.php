<?php

declare(strict_types=1);

namespace App\Controllers\Host;

use App\Controllers\BaseController;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * U-08 開催者専用画面 ／ U-09 結果確認（同じ画面のモーダル）
 */
final class HostController extends BaseController
{
    /**
     * U-08。トークンがなければ合言葉の入力画面、確定済みなら結果ページへ
     */
    public function show(string $slug): RedirectResponse|string
    {
        $oogiri = $this->findOogiriOrFail($slug);

        if (! service('host')->isAuthorized($oogiri)) {
            return view('host/u08_recover', [
                'oogiri' => $oogiri,
                'errors' => session()->getFlashdata('errors') ?? [],
            ]);
        }

        if (in_array($oogiri->status(), [OogiriStatus::Decided, OogiriStatus::NoResult], true)) {
            return redirect()->to('/o/' . $slug);
        }

        $format = service('display');
        $share  = service('share');

        return view('host/u08_host', [
            'oogiri'             => $oogiri,
            'isOpen'             => $oogiri->isOpen(),
            'startsAt'           => $format->datetime($oogiri->starts_at),
            'endsAt'             => $format->datetime($oogiri->ends_at),
            'remaining'          => $format->remaining($oogiri->ends_at),
            'selectionRemaining' => $oogiri->selection_deadline !== null ? $format->remaining($oogiri->selection_deadline) : '',
            'pv'                 => $format->number($oogiri->pv),
            'answers'            => service('answer')->listVisible($oogiri, 1)['items'],
            'shareUrl'           => $share->url($oogiri),
            'intentUrl'          => $share->recruitIntent($oogiri),
            'errors'             => session()->getFlashdata('errors') ?? [],
        ]);
    }

    /**
     * 合言葉で本人確認
     */
    public function recover(string $slug): RedirectResponse
    {
        $oogiri = $this->findOogiriOrFail($slug);

        try {
            service('host')->recover($oogiri, (string) $this->request->getPost('passphrase'));
        } catch (ValidationException $e) {
            return redirect()->back()->with('errors', $e->errors);
        } catch (AppException $e) {
            return redirect()->back()->with('errors', ['_' => $e->getMessage()]);
        }

        return redirect()->to('/host/' . $slug);
    }

    public function favorite(string $slug, int $answerId): RedirectResponse
    {
        service('host')->toggleFavorite($this->findOogiriOrFail($slug), $answerId);

        return redirect()->to('/host/' . $slug);
    }

    /**
     * U-09「この内容で結果を発表する」→ 確定して結果ページへ
     */
    public function decide(string $slug): RedirectResponse
    {
        $oogiri = $this->findOogiriOrFail($slug);
        $ranks  = (array) $this->request->getPost('ranks');

        try {
            service('host')->decide($oogiri, $ranks);
        } catch (ValidationException $e) {
            return redirect()->back()->with('errors', $e->errors);
        } catch (AppException $e) {
            return redirect()->back()->with('errors', ['_' => $e->getMessage()]);
        }

        return redirect()->to('/o/' . $slug . '?fixture=decided'); // TODO(inner): 本実装後は ?fixture= を外す
    }

    public function delete(string $slug): RedirectResponse
    {
        service('host')->delete($this->findOogiriOrFail($slug));

        return redirect()->to('/')->with('toast', '大喜利を削除しました');
    }
}
