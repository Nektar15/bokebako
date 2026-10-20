<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Entities\Answer;
use App\Entities\Oogiri;

/**
 * U-04 回答画面の組み立て。募集URL（OogiriController）と回答シェア用URL（AnswerController）で共用する。
 */
trait RendersAnswerPage
{
    protected function renderAnswerPage(Oogiri $oogiri, ?Answer $sharedAnswer = null): string
    {
        $format = service('display');

        return view('front/u04_answer', [
            'oogiri'          => $oogiri,
            'remaining'       => $format->remaining($oogiri->ends_at),
            'progressPercent' => $format->progressPercent($oogiri->starts_at, $oogiri->ends_at),
            'answerMax'       => service('settings')->int('answer_max'),
            'errors'          => session()->getFlashdata('errors') ?? [],
            'old'             => ['body' => (string) old('body')],
            'sharedAnswer'    => $sharedAnswer,
            'ads'             => service('ads')->slots(),
        ]);
    }
}
