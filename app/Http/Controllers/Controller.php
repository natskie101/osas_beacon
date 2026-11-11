<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Exceptions\ValidationException;
use App\Support\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

abstract class Controller
{
    /**
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    protected function validate(Request $request, array $rules): array
    {
        // input() (not all()) so uploaded files never reach the validator
        // or the session's old-input flash.
        $data = $request->input();

        $validator = Validator::make($data, $rules);

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

    /**
     * Adapts Laravel's UploadedFile to the $_FILES-shaped array consumed by
     * App\Services\FileStorage and App\Services\UserService, so upload
     * logic keeps working while requests are migrated to native Laravel.
     *
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}|null
     */
    protected function uploadedFile(Request $request, string $key): ?array
    {
        $file = $request->file($key);

        if (is_array($file)) {
            $file = $file[0] ?? null;
        }

        if (!$file instanceof UploadedFile) {
            return null;
        }

        return [
            'name' => $file->getClientOriginalName(),
            'type' => $file->getClientMimeType(),
            'tmp_name' => (string) $file->getRealPath(),
            'error' => $file->getError(),
            'size' => (int) $file->getSize(),
        ];
    }
}
