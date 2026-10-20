<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;

/**
 * 静的ページ U-14〜U-17。本文は発注者支給（View に直接書く。ページキャッシュの対象）
 */
final class PageController extends BaseController
{
    public function about(): string
    {
        return view('front/u14_about');
    }

    /** 規約の本文は発注者支給。届くまでは一般的なひな形を置き、本番以外では「ひな形」の注記を出す */
    public function terms(): string
    {
        return view('front/u15_terms', ['showDraftNote' => ENVIRONMENT !== 'production']);
    }

    public function privacy(): string
    {
        return view('front/u16_privacy', ['showDraftNote' => ENVIRONMENT !== 'production']);
    }

    public function guidelines(): string
    {
        return view('front/u17_guidelines');
    }
}
