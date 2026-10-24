<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Enums\ErrorCode;
use App\Enums\OgpKind;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Libraries\Captcha;
use App\Services\AnswerService;
use App\Services\OgpService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 回答まわり：U-05（登録）→ U-06（完了）→ U-07（一覧）、回答シェア用URL
 */
final class AnswerController extends BaseController
{
    use RendersAnswerPage;

    /**
     * U-05「同意して回答する」→ 登録して U-06 へ
     */
    public function create(string $slug): ResponseInterface
    {
        $oogiri = $this->findOogiriOrFail($slug);

        try {
            service('answer')->create($oogiri, ['body' => (string) $this->request->getPost('body'), 'captcha' => (string) $this->request->getPost(Captcha::FIELD)]);
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->with('errors', $e->errors);
        } catch (AppException $e) {
            // 書いている間に締め切られた：戻り先の U-11 はキャッシュ配信でエラーを出せないので、理由を添えた U-11 をこの場で返す（N-09）
            if ($e->errorCode === ErrorCode::Closed) {
                return $this->response->setStatusCode(409)->setBody(view('front/u11_closed', [
                    'oogiri'     => $oogiri,
                    'ogpUrl'     => service('ogp')->url(OgpKind::Recruit, $oogiri->slug),
                    'justClosed' => true,
                ]));
            }

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
            'answerIntentUrl' => $share->answerIntent($oogiri, $answer, true), // U-06 は本人の回答
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
        $sort   = AnswerService::sortKey($this->request->getGet('sort'));
        $format = service('display');
        $share  = service('share');

        return view('front/u07_list', [
            'oogiri'          => $oogiri,
            'remaining'       => $format->remaining($oogiri->ends_at),
            'progressPercent' => $format->progressPercent($oogiri->starts_at, $oogiri->ends_at),
            'sort'            => $sort,
            'sorts'           => AnswerService::SORTS,
            'answers'         => service('answer')->listVisible($oogiri, $page, true, $sort), // U-07 はキャッシュしないので本人の印を付けてよい
            'answerIntentUrl' => static fn ($answer) => $share->answerIntent($oogiri, $answer, (bool) $answer->is_mine), // 本人の回答だけ「俺の回答」（BUG-008）
        ]);
    }

    /**
     * 回答シェア用URL（未確定事項5：募集中は U-04 に回答を1件添えて表示。終了後は回答と「結果を見る」だけのページ）
     *
     * 存在しない回答・非表示や削除の回答・非表示や削除中の大喜利の回答は 404（BUG-004、ADR 0015。中身を出さない）
     */
    public function share(string $slug, int $answerId): string
    {
        $oogiri = $this->findOogiriOrFail($slug);

        if (! in_array($oogiri->status()->value, OgpService::PUBLIC_STATUSES, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            $answer = service('answer')->find($oogiri, $answerId);
        } catch (NotFoundException) {
            throw PageNotFoundException::forPageNotFound();
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
