<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Exceptions\ForbiddenHttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Validator;

abstract class FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string|list<string>> */
    abstract public function rules(): array;

    /** @return array<string, mixed> */
    public function validate(Request $request): array
    {
        if (!$this->authorize()) {
            throw new ForbiddenHttpException();
        }

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors(), $request->all());
        }

        return $validator->validated();
    }
}
