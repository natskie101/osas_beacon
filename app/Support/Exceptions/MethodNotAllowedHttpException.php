<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

final class MethodNotAllowedHttpException extends HttpException
{
    /** @param list<string> $allowed */
    public function __construct(public readonly array $allowed = [])
    {
        parent::__construct(405, 'This request method is not supported.', ['Allow' => implode(', ', $allowed)]);
    }
}
