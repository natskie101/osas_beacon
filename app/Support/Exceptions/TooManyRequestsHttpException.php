<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

final class TooManyRequestsHttpException extends HttpException
{
    public function __construct(string $message = 'Too many attempts. Please try again later.', ?int $retryAfter = null)
    {
        parent::__construct(429, $message, ['Retry-After' => (string) max(1, $retryAfter ?? 60)]);
    }
}
