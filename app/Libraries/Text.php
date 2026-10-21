<?php

declare(strict_types=1);

namespace App\Libraries;

use Normalizer;

/**
 * 投稿文字列の整え方（監査 2026-10-21）
 */
final class Text
{
    /**
     * 見えない文字（ゼロ幅スペース・ゼロ幅接合子・BOM・単語結合子など）
     */
    private const INVISIBLE = '\x{200B}-\x{200D}\x{2060}\x{FEFF}';

    /**
     * 前後の空白を取る。半角の空白に加えて、全角スペースと見えない文字も取る
     * （trim() は半角しか取らないので、「　　　」や見えない文字だけのお題・回答が通っていた）
     */
    public static function clean(string $text): string
    {
        return (string) preg_replace('/\A[\s\x{3000}' . self::INVISIBLE . ']+|[\s\x{3000}' . self::INVISIBLE . ']+\z/u', '', $text);
    }

    /**
     * NG ワードの照合用：見えない文字を除き、全角・半角などをそろえる（NFKC）。「死\u{200B}ね」「ｓｈｉｎｅ」ですり抜けさせない
     */
    public static function forMatch(string $text): string
    {
        $text = (string) preg_replace('/[' . self::INVISIBLE . ']/u', '', $text);

        return class_exists(Normalizer::class) ? (string) Normalizer::normalize($text, Normalizer::FORM_KC) : $text;
    }
}
