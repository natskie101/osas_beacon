<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Exceptions\ValidationException;
use App\Support\Request as PortalRequest;
use App\Support\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

abstract class Controller
{
    /**
     * Accepts both the native Illuminate request and the legacy portal
     * request while controllers are migrated to native Laravel.
     *
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    protected function validate(PortalRequest|Request $request, array $rules): array
    {
        // input() (not all()) so uploaded files never reach the validator
        // or the session's old-input flash.
        $data = $request->input();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors(), $data);
        }

        return $validator->validated();
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
