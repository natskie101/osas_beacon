<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

final class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = 'The page you requested could not be found.')
    {
        parent::__construct(404, $message);
    }
}
