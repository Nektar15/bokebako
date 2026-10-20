<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ErrorCode;

/** 入力エラー。項目ごとの文言を持つ */
final class ValidationException extends AppException
{
    /** @param array<string, string> $errors 項目名 => 文言 */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(ErrorCode::ValidationFailed);
    }
}
