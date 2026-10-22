<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Entities\Oogiri;
use App\Enums\OgpKind;
use App\Enums\OogiriStatus;
use App\Services\PageCacheService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * 募集URL /o/{slug}。状態で U-04 / U-10 / U-11 / U-12 / U-13 を出し分ける（FEATURES 0-4）
 */
final class OogiriController extends BaseController
{
    use RendersAnswerPage;

    public function show(string $slug): string
    {
        $oogiri = $this->findOogiriOrFail($slug);

        return match ($oogiri->status()) {
            OogiriStatus::Open                          => $this->renderAnswerPage($oogiri),
            OogiriStatus::AwaitingSelection             => $this->closedPage($oogiri),
            OogiriStatus::Decided                       => $this->resultPage($oogiri),
            OogiriStatus::NoResult                      => $this->noResultPage($oogiri),
            OogiriStatus::Hidden, OogiriStatus::Deleted => $this->hiddenPage($oogiri),
        };
    }

    /**
     * U-03 開催完了（開催者トークンを持つブラウザだけ。なければ募集URLへ）
     */
    public function created(string $slug): RedirectResponse|string
    {
        $oogiri = $this->findOogiriOrFail($slug);

        if (! service('host')->isAuthorized($oogiri)) {
            return redirect()->to('/o/' . $slug);
        }

        return view('front/u03_created', [
            'oogiri'    => $oogiri,
            'endsAt'    => service('display')->datetime($oogiri->ends_at),
            'shareUrl'  => service('share')->url($oogiri),
            'intentUrl' => service('share')->recruitIntent($oogiri),
            'hostUrl'   => site_url('host/' . $slug),
            'ogpUrl'    => service('ogp')->url(OgpKind::Recruit, $slug),
            'hostToken' => service('host')->tokenFromRequest($oogiri), // 開催時に発行したトークン（この画面と U-08 からだけ渡す）
        ]);
    }

    /**
     * U-11 募集終了（結果選択待ち）
     */
    private function closedPage(Oogiri $oogiri): string
    {
        $this->cacheFirstPage();

        // og:image は、画像がなければ既定の画像にする（監査 2026-10-21）
        return view('front/u11_closed', [
            'oogiri'     => $oogiri,
            'ogpUrl'     => service('ogp')->url(OgpKind::Recruit, $oogiri->slug),
            'justClosed' => false,
        ]);
    }

    /**
     * U-10 結果発表
     */
    private function resultPage(Oogiri $oogiri): string
    {
        $page   = min(10000, max(1, (int) $this->request->getGet('page'))); // 巨大な値で offset が溢れないように上限
        $ranked = service('answer')->ranked($oogiri);
        $format = service('display');
        $share  = service('share');
        $this->cacheFirstPage(); // 結果ページはキャッシュ配信（SPEC 4.1）。確定・非表示で捨てる

        return view('front/u10_result', [
            'oogiri'          => $oogiri,
            'period'          => $format->period($oogiri->starts_at, $oogiri->ends_at),
            'ogpUrl'          => service('ogp')->url(OgpKind::Result, $oogiri->slug),
            'ranked'          => $ranked,
            'answers'         => service('answer')->listVisible($oogiri, $page, false), // 共有キャッシュに個人の情報を載せない
            'resultIntentUrl' => $share->resultIntent($oogiri, $ranked),
            'answerIntentUrl' => static fn ($answer) => $share->answerIntent($oogiri, $answer),
            'ads'             => ['deferred' => true], // キャッシュに広告を焼き込まない（GET /api/v1/ads で表示のたびに取る）
        ]);
    }

    /**
     * U-12 結果未選出
     */
    private function noResultPage(Oogiri $oogiri): string
    {
        $page  = min(10000, max(1, (int) $this->request->getGet('page'))); // 巨大な値で offset が溢れないように上限
        $share = service('share');
        $this->cacheFirstPage();

        return view('front/u12_no_result', [
            'oogiri'          => $oogiri,
            'ogpUrl'          => service('ogp')->url(OgpKind::Result, $oogiri->slug), // S-02 で作る結果用（選出なし）。なければ既定の画像
            'period'          => service('display')->period($oogiri->starts_at, $oogiri->ends_at),
            'answers'         => service('answer')->listVisible($oogiri, $page, false), // 共有キャッシュに個人の情報を載せない
            'answerIntentUrl' => static fn ($answer) => $share->answerIntent($oogiri, $answer),
            'ads'             => ['deferred' => true], // キャッシュに広告を焼き込まない（GET /api/v1/ads で表示のたびに取る）
        ]);
    }

    /**
     * 結果・結果未選出・募集終了の1ページ目（?page= なし）だけキャッシュする
     *
     * キャッシュのキーは page クエリを含む（Config\Cache::$cacheQueryString）。?page=2 以降や ?page=1 を保存すると、
     * PageCacheService::purgeOogiri() が捨てられないキャッシュが残るため、保存しない（レビュー指摘 5）
     */
    private function cacheFirstPage(): void
    {
        if ($this->request->getGet('page') === null) {
            $this->cachePage(PageCacheService::TTL_RESULT);
        }
    }

    /**
     * U-13 非表示
     */
    private function hiddenPage(Oogiri $oogiri): string
    {
        return view('front/u13_hidden', [
            'oogiri' => $oogiri,
            'isHost' => service('host')->isAuthorized($oogiri),
        ]);
    }
}
