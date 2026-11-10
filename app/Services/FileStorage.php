<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
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

    /** @var array<string, string> */
    public const ORIENTATION_MEDIA = [
        'application/pdf' => 'pdf',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    /**
     * @param array<string, mixed> $file
     * @param array<string, string>|null $allowed
     * @return array<string, mixed>
     */
    public static function storeUpload(
        array $file,
        string $directory,
        string $entityType,
        string $entityId,
        ?int $ownerId = null,
        int $maxBytes = self::MAX_BYTES,
        ?array $allowed = null,
    ): array {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Choose a file to upload.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The upload did not complete. Please try again.');
        }

        $size = (int) ($file['size'] ?? 0);
        $allowed = $allowed ?? self::ALLOWED;

        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('The file is larger than the allowed upload size.');
        }

        $temporary = (string) ($file['tmp_name'] ?? '');

        if ($temporary === '' || !is_uploaded_file($temporary)) {
            throw new RuntimeException('The uploaded file could not be verified.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary) ?: '';

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('This file type is not accepted.');
        }

        $extension = $allowed[$mime];
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

        $id = DB::table('files')->insertGetId([
            'owner_user_id' => $ownerId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'original_name' => $originalName,
            'stored_name' => $relativePath,
            'mime_type' => $mime,
            'size_bytes' => $size,
            'checksum' => hash_file('sha256', $absolutePath) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $row = DB::table('files')->where('id', $id)->first();

        if ($row === null) {
            throw new RuntimeException('The uploaded file record could not be loaded.');
        }

        return (array) $row;
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

        DB::table('files')->where('id', (int) $file['id'])->delete();
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
