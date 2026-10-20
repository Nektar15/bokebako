<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Libraries\Captcha;
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

        $page   = max(1, (int) $this->request->getGet('page'));
        $format = service('display');
        $share  = service('share');

        return view('front/u07_list', [
            'oogiri'          => $oogiri,
            'remaining'       => $format->remaining($oogiri->ends_at),
            'progressPercent' => $format->progressPercent($oogiri->starts_at, $oogiri->ends_at),
            'answers'         => service('answer')->listVisible($oogiri, $page),
            'answerIntentUrl' => static fn ($answer) => $share->answerIntent($oogiri, $answer),
        ]);
    }

    /**
     * 回答シェア用URL（未確定事項5：募集中は U-04 に回答を1件添えて表示。終了後は募集URLへ）
     */
    public function share(string $slug, int $answerId): RedirectResponse|string
    {
        $oogiri = $this->findOogiriOrFail($slug);

        if (! $oogiri->isOpen()) {
            return redirect()->to('/o/' . $slug);
        }

        try {
            $answer = service('answer')->find($oogiri, $answerId);
        } catch (NotFoundException) {
            return redirect()->to('/o/' . $slug);
        }
        service('ogp')->ensureAnswer($oogiri, $answer); // 回答シェア用 OGP は初回アクセス時に生成（S-07）

        return $this->renderAnswerPage($oogiri, $answer);
    }
}
