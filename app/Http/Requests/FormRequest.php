<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Exceptions\ForbiddenHttpException;
use App\Support\Exceptions\ValidationException;
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
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
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

        // Disabled form inputs are never submitted, but validated() reports
        // them as null — writing those nulls would erase existing values
        // (e.g. a staff account's department, which the edit form disables
        // for non-student targets). Return only the fields the request
        // actually carried, so an absent field means "leave unchanged".
        return array_intersect_key($validator->validated(), $data);
    }
}
