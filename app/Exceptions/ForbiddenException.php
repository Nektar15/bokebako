<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ErrorCode;

final class ForbiddenException extends AppException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(ErrorCode::Forbidden, $message);
    }
}
