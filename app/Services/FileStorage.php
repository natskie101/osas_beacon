<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DB;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Response;
use App\Support\Str;
use RuntimeException;

final class FileStorage
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const ALLOWED = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    public static function storeUpload(
        array $file,
        string $directory,
        string $entityType,
        string $entityId,
        ?int $ownerId = null,
    ): array {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Choose a file to upload.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The upload did not complete. Please try again.');
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('The file must be 5 MB or smaller.');
        }

        $temporary = (string) ($file['tmp_name'] ?? '');

        if ($temporary === '' || !is_uploaded_file($temporary)) {
            throw new RuntimeException('The uploaded file could not be verified.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary) ?: '';

        if (!isset(self::ALLOWED[$mime])) {
            throw new RuntimeException('Only PDF, JPG and PNG files are accepted.');
        }

        $extension = self::ALLOWED[$mime];
        $relativeDirectory = trim(str_replace('\\', '/', $directory), '/');
        $absoluteDirectory = BASE_PATH . '/storage/uploads/' . $relativeDirectory;

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('The upload directory could not be created.');
        }

        $storedName = Str::random(20) . '.' . $extension;
        $relativePath = $relativeDirectory . '/' . $storedName;
        $absolutePath = BASE_PATH . '/storage/uploads/' . $relativePath;
        $originalName = mb_substr(basename((string) ($file['name'] ?? 'upload.' . $extension)), 0, 180);

        if ($originalName === '') {
            $originalName = 'upload.' . $extension;
        }

        if (!move_uploaded_file($temporary, $absolutePath)) {
            throw new RuntimeException('The file could not be saved. Please try again.');
        }

        $id = DB::insert(
            'INSERT INTO files
                (owner_user_id, entity_type, entity_id, original_name, stored_name, mime_type, size_bytes, checksum, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $ownerId,
                $entityType,
                $entityId,
                $originalName,
                $relativePath,
                $mime,
                $size,
                hash_file('sha256', $absolutePath) ?: null,
                date('Y-m-d H:i:s'),
            ],
        );

        $row = DB::first('SELECT * FROM files WHERE id = ?', [$id]);

        if ($row === null) {
            throw new RuntimeException('The uploaded file record could not be loaded.');
        }

        return $row;
    }

    /** @param array<string, mixed> $file */
    public static function path(array $file): string
    {
        $relative = str_replace(['\\', '..'], ['/', ''], (string) ($file['stored_name'] ?? ''));

        return BASE_PATH . '/storage/uploads/' . ltrim($relative, '/');
    }

    /** @param array<string, mixed> $file */
    public static function delete(array $file): void
    {
        $path = self::path($file);

        if (is_file($path)) {
            @unlink($path);
        }

        DB::delete('DELETE FROM files WHERE id = ?', [(int) $file['id']]);
    }

    /** @param array<string, mixed> $file */
    public static function download(array $file): Response
    {
        $path = self::path($file);

        if (!is_file($path)) {
            throw new NotFoundHttpException('The stored file could not be found.');
        }

        return Response::download(
            (string) ($file['original_name'] ?? 'download'),
            (string) file_get_contents($path),
            (string) ($file['mime_type'] ?? 'application/octet-stream'),
        );
    }
}
