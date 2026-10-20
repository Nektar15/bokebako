<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Entities\Answer;
use App\Entities\Oogiri;
use App\Enums\OgpKind;
use App\Libraries\Captcha;
use App\Services\RateLimitService;

/**
 * U-04 回答画面の組み立て。募集URL（OogiriController）と回答シェア用URL（AnswerController）で共用する。
 */
trait RendersAnswerPage
{
    protected function renderAnswerPage(Oogiri $oogiri, ?Answer $sharedAnswer = null): string
    {
        $format = service('display');
        service('event')->recordVisit($oogiri->id); // 募集URL流入（S-06）

        return view('front/u04_answer', [
            'oogiri'          => $oogiri,
            'remaining'       => $format->remaining($oogiri->ends_at),
            'progressPercent' => $format->progressPercent($oogiri->starts_at, $oogiri->ends_at),
            'answerMax'       => service('settings')->int('answer_max'),
            'errors'          => session()->getFlashdata('errors') ?? [],
            'old'             => ['body' => (string) old('body')],
            'sharedAnswer'    => $sharedAnswer,
            'ads'             => service('ads')->slots(),
            'ogpUrl'          => $sharedAnswer !== null ? service('ogp')->url(OgpKind::Answer, (string) $sharedAnswer->id) : service('ogp')->url(OgpKind::Recruit, $oogiri->slug),
            'captcha'         => service('rateLimit')->needsCaptcha(RateLimitService::ACTION_ANSWER) ? (new Captcha())->siteKey() : '',
        ]);
    }
}
