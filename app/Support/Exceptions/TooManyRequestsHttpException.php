<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

final class TooManyRequestsHttpException extends HttpException
{
    public function __construct(string $message = 'Too many attempts. Please try again later.')
    {
        parent::__construct(429, $message, ['Retry-After' => '60']);
    }
}
