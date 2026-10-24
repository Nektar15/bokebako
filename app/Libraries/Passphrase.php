<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * 合言葉の整え方・保存・照合（BUG-002・BUG-006、ADR 0015）
 *
 * 開催時と本人確認時で同じ整え方をする（前後の全角スペース・見えない文字を取り、全角と半角を NFKC でそろえる）。
 * bcrypt は先頭 72 バイトしか見ないので、SHA-256 にしてから password_hash() に渡す。
 */
final class Passphrase
{
    public const MIN = 4;
    public const MAX = 32;

    /**
     * SHA-256 を通した新しい形式のハッシュの印（古い形式と見分ける）
     */
    private const PREFIX = 'v2$';

    public static function normalize(string $passphrase): string
    {
        return Text::forMatch(Text::clean($passphrase));
    }

    /**
     * 入力の誤りを返す。正しければ null
     */
    public static function error(string $passphrase): ?string
    {
        $length = mb_strlen(self::normalize($passphrase));

        if ($length === 0) {
            return '合言葉を入力してください';
        }
        if ($length < self::MIN || $length > self::MAX) {
            return '合言葉は' . self::MIN . '〜' . self::MAX . '文字で入力してください';
        }

        return null;
    }

    public static function hash(string $passphrase): string
    {
        return self::PREFIX . password_hash(self::digest($passphrase), PASSWORD_DEFAULT);
    }

    public static function verify(string $passphrase, string $hash): bool
    {
        if (str_starts_with($hash, self::PREFIX)) {
            return password_verify(self::digest($passphrase), substr($hash, strlen(self::PREFIX)));
        }

        // 2026-10-22 より前の形式：開催時は Text::clean() だけで整え、そのまま password_hash() していた
        return password_verify(Text::clean($passphrase), $hash) || password_verify(trim($passphrase), $hash);
    }

    private static function digest(string $passphrase): string
    {
        return hash('sha256', self::normalize($passphrase));
    }
}
