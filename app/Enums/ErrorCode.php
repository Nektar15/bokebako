<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * API と画面で返すエラーコード（CODING_RULES 11章）
 * ここにないコードは返さない。
 */
enum ErrorCode: string
{
    case ValidationFailed = 'VALIDATION_FAILED'; // 400
    case NgWord           = 'NG_WORD';           // 400
    case Forbidden        = 'FORBIDDEN';         // 403
    case NotFound         = 'NOT_FOUND';         // 404
    case Closed           = 'CLOSED';            // 409 締切後の回答など
    case AlreadyDecided   = 'ALREADY_DECIDED';   // 409
    case RateLimited      = 'RATE_LIMITED';      // 429
    case ServerError      = 'SERVER_ERROR';      // 500

    public function httpStatus(): int
    {
        return match ($this) {
            self::ValidationFailed, self::NgWord => 400,
            self::Forbidden                      => 403,
            self::NotFound                       => 404,
            self::Closed, self::AlreadyDecided   => 409,
            self::RateLimited                    => 429,
            self::ServerError                    => 500,
        };
    }

    /**
     * 利用者に見せる文言
     */
    public function message(): string
    {
        return match ($this) {
            self::ValidationFailed => '入力内容を確認してください',
            self::NgWord           => '投稿できない言葉が含まれています',
            self::Forbidden        => 'この操作はできません',
            self::NotFound         => '見つかりませんでした',
            self::Closed           => 'この大喜利の募集は終了しています',
            self::AlreadyDecided   => '結果はすでに確定しています',
            self::RateLimited      => '少し時間をおいてから投稿してください',
            self::ServerError      => 'エラーが発生しました。時間をおいてもう一度お試しください',
        };
    }
}
