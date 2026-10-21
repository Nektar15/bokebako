<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ErrorCode;
use CodeIgniter\Exceptions\HTTPExceptionInterface;
use RuntimeException;

/**
 * 業務エラーの親（CODING_RULES 5章）
 * Service が投げ、Controller が受けて画面か JSON にする。false や null で失敗を表さない。
 *
 * Controller が受け損ねたときも 500 にせず、ErrorCode の状態コード（403・409・429 など）のエラー画面にする
 * （HTTPExceptionInterface。監査 2026-10-21）
 */
class AppException extends RuntimeException implements HTTPExceptionInterface
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        ?string $message = null,
    ) {
        parent::__construct($message ?? $errorCode->message(), $errorCode->httpStatus());
    }
}
