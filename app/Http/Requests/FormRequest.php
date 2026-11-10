<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Exceptions\ForbiddenHttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Request as PortalRequest;
use App\Support\Validator;
use Illuminate\Http\Request;

abstract class FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string|list<string>> */
    abstract public function rules(): array;

    /**
     * Accepts both the native Illuminate request and the legacy portal
     * request while controllers are migrated to native Laravel.
     *
     * @return array<string, mixed>
     */
    public function validate(PortalRequest|Request $request): array
    {
        if (!$this->authorize()) {
            throw new ForbiddenHttpException();
        }

        // input() (not all()) so uploaded files never reach the validator
        // or the session's old-input flash.
        $data = $request->input();

        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors(), $data);
        }

        return $validator->validated();
    }
}
