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

        // 確定済み・結果未選出・非表示・削除済みは、選ぶ画面を出さずに募集URL（U-10・U-12・U-13）へ（監査 2026-10-21）
        if (! in_array($oogiri->status(), [OogiriStatus::Open, OogiriStatus::AwaitingSelection], true)) {
            return redirect()->to('/o/' . $slug);
        }

        $format  = service('display');
        $share   = service('share');
        $host    = service('host');
        $page    = min(10000, max(1, (int) $this->request->getGet('page'))); // 巨大な値で offset が溢れないように上限
        $picking = ! $oogiri->isOpen();

        return view('host/u08_host', [
            'oogiri'             => $oogiri,
            'isOpen'             => $oogiri->isOpen(),
            'startsAt'           => $format->datetime($oogiri->starts_at),
            'endsAt'             => $format->datetime($oogiri->ends_at),
            'remaining'          => $format->remaining($oogiri->ends_at),
            'selectionRemaining' => $oogiri->selection_deadline !== null ? $format->remaining($oogiri->selection_deadline) : '',
            'pv'                 => $format->number($oogiri->pv),
            'favorites'          => $picking && $page === 1 ? $host->favorites($oogiri) : [],
            'favoriteCount'      => $host->favoriteCount($oogiri),
            'answers'            => $host->answers($oogiri, $page, $picking),
            'moreUrl'            => site_url('host/' . $slug) . '?page=' . ($page + 1) . '#answers',
            'hostToken'          => $host->tokenFromRequest($oogiri),
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

        return redirect()->to('/host/' . $slug)->withCookies();
    }

    /**
     * localStorage に残っていた開催者トークンで Cookie を復元する（JS からの POST。U-08）
     */
    public function restore(string $slug): RedirectResponse
    {
        $oogiri = $this->findOogiriOrFail($slug);
        $ok     = service('host')->restoreToken($oogiri, (string) $this->request->getPost('token'));

        return redirect()->to('/host/' . $slug . ($ok ? '' : '?restored=1'))->withCookies();
    }

    public function favorite(string $slug, int $answerId): RedirectResponse
    {
        try {
            service('host')->toggleFavorite($this->findOogiriOrFail($slug), $answerId);
        } catch (AppException) {
            // 開催者でない（Cookie が切れた）ときは開催者画面へ戻す（合言葉の入力が出る）
        }

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

        return redirect()->to('/o/' . $slug);
    }

    public function delete(string $slug): RedirectResponse
    {
        try {
            service('host')->delete($this->findOogiriOrFail($slug));
        } catch (AppException $e) {
            return redirect()->to('/host/' . $slug)->with('errors', ['_' => $e->getMessage()]);
        }

        return redirect()->to('/')->with('toast', '大喜利を削除しました');
    }
}
