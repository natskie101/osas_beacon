<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;
use App\Support\View;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($view, $data), $status);
    }

    /** @param array<string, mixed> $data */
    protected function page(string $layout, string $view, array $data = [], int $status = 200): Response
    {
        return Response::html(View::page($layout, $view, $data), $status);
    }

    /**
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    protected function validate(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors(), $request->all());
        }

        return $validator->validated();
    }
}
