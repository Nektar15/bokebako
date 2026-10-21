<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Enums\OgpKind;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Libraries\Captcha;
use App\Services\OgpService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * 回答まわり：U-05（登録）→ U-06（完了）→ U-07（一覧）、回答シェア用URL
 */
final class AnswerController extends BaseController
{
    use RendersAnswerPage;

    /**
     * U-05「同意して回答する」→ 登録して U-06 へ
     */
    public function create(string $slug): RedirectResponse
    {
        $oogiri = $this->findOogiriOrFail($slug);

        try {
            service('answer')->create($oogiri, ['body' => (string) $this->request->getPost('body'), 'captcha' => (string) $this->request->getPost(Captcha::FIELD)]);
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->with('errors', $e->errors);
        } catch (AppException $e) {
            return redirect()->back()->withInput()->with('errors', ['_' => $e->getMessage()]);
        }

        return redirect()->to('/o/' . $slug . '/done');
    }

    /**
     * U-06 回答完了（直前の回答がなければ募集URLへ）
     */
    public function done(string $slug): RedirectResponse|string
    {
        $oogiri = $this->findOogiriOrFail($slug);
        $answer = service('answer')->lastPosted($oogiri);

        if ($answer === null) {
            return redirect()->to('/o/' . $slug);
        }

        $share = service('share');

        return view('front/u06_answered', [
            'oogiri'          => $oogiri,
            'answer'          => $answer,
            'answerIntentUrl' => $share->answerIntent($oogiri, $answer),
            'oogiriIntentUrl' => $share->recruitIntent($oogiri),
        ]);
    }

    /**
     * U-07 回答一覧（回答済みのブラウザだけ）
     */
    public function index(string $slug): RedirectResponse|string
    {
        $oogiri = $this->findOogiriOrFail($slug);

        if (! $oogiri->isOpen()) {
            return redirect()->to('/o/' . $slug);
        }
        if (! service('answer')->hasAnswered($oogiri)) {
            return redirect()->to('/o/' . $slug);
        }

        $page   = min(10000, max(1, (int) $this->request->getGet('page'))); // 巨大な値で offset が溢れないように上限
        $format = service('display');
        $share  = service('share');

        return view('front/u07_list', [
            'oogiri'          => $oogiri,
            'remaining'       => $format->remaining($oogiri->ends_at),
            'progressPercent' => $format->progressPercent($oogiri->starts_at, $oogiri->ends_at),
            'answers'         => service('answer')->listVisible($oogiri, $page, true), // U-07 はキャッシュしないので本人の印を付けてよい
            'answerIntentUrl' => static fn ($answer) => $share->answerIntent($oogiri, $answer),
        ]);
    }

    /**
     * 回答シェア用URL（未確定事項5：募集中は U-04 に回答を1件添えて表示。終了後は回答と「結果を見る」だけのページ。非表示・削除中は募集URLへ）
     */
    public function share(string $slug, int $answerId): RedirectResponse|string
    {
        $oogiri = $this->findOogiriOrFail($slug);

        // 非表示・削除中は募集URL（U-13）へ
        if (! in_array($oogiri->status()->value, OgpService::PUBLIC_STATUSES, true)) {
            return redirect()->to('/o/' . $slug);
        }

        try {
            $answer = service('answer')->find($oogiri, $answerId);
        } catch (NotFoundException) {
            return redirect()->to('/o/' . $slug);
        }
        service('ogp')->ensureAnswer($oogiri, $answer); // 回答シェア用 OGP は初回アクセス時に生成（S-07）

        if ($oogiri->isOpen()) {
            return $this->renderAnswerPage($oogiri, $answer);
        }

        // 募集が終わったあと（U-10・U-12 の各回答の「Xでシェア」）も、カードに回答の画像を出す（SPEC U-10、監査 2026-10-21）
        return view('front/answer_share_closed', [
            'oogiri'      => $oogiri,
            'answer'      => $answer,
            'description' => mb_strimwidth($answer->body, 0, 120, '…'),
            'ogpUrl'      => service('ogp')->url(OgpKind::Answer, (string) $answer->id),
            'nextLabel'   => $oogiri->status() === OogiriStatus::AwaitingSelection ? 'この大喜利を見る' : '結果を見る',
        ]);
    }
}
