<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }
}
