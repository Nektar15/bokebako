<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ErrorCode;
use RuntimeException;

/**
 * 業務エラーの親（CODING_RULES 5章）
 * Service が投げ、Controller が受けて画面か JSON にする。false や null で失敗を表さない。
 */
class AppException extends RuntimeException
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        ?string $message = null,
    ) {
        parent::__construct($message ?? $errorCode->message(), $errorCode->httpStatus());
    }
}
