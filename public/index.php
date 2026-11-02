<?php

declare(strict_types=1);

use App\Support\Exceptions\HttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;

$router = require __DIR__ . '/../bootstrap/app.php';

$request = Request::capture();

try {
    $response = $router->dispatch($request);
} catch (ValidationException $exception) {
    Session::flashErrors($exception->errors());
    Session::flashOld($exception->old());
    $response = Response::redirect($request->previousUrl());
} catch (HttpException $exception) {
    $response = Response::html(
        View::render('errors/error', [
            'status' => $exception->statusCode,
            'message' => $exception->getMessage() ?: 'The request could not be completed.',
            'debug' => (bool) config('app.debug', false),
            'exception' => $exception,
        ]),
        $exception->statusCode,
        $exception->headers,
    );
} catch (Throwable $exception) {
    error_log((string) $exception);

    $response = Response::html(
        View::render('errors/error', [
            'status' => 500,
            'message' => 'An unexpected server error occurred. The details have been written to the application log.',
            'debug' => (bool) config('app.debug', false),
            'exception' => $exception,
        ]),
        500,
    );
}

$response->send();
