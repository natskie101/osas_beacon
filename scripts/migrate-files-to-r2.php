<?php

declare(strict_types=1);

/**
 * Moves files that fell back to base64-in-DB (or local disk) into Cloudflare R2.
 *
 * Why this exists: FileStorage::storeUpload() swallows R2 failures and falls
 * back to storing the bytes in files.file_data. Every upload in this database
 * took that path, so a 27 MB video was living in a text column. This re-uploads
 * each one, verifies the object is readable back from R2, rewrites stored_name
 * with the r2: prefix, and only then clears file_data.
 *
 * Usage:
 *   php scripts/migrate-files-to-r2.php            # apply
 *   php scripts/migrate-files-to-r2.php --dry-run  # report only, change nothing
 *
 * The row is never altered before the object has been confirmed readable from
 * the bucket, so a failure mid-way leaves the file where it was.
 */

$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FileStorage;
use App\Services\R2Storage;
use Illuminate\Support\Facades\DB;

$dryRun = in_array('--dry-run', $argv ?? [], true);

echo 'R2 configured: ' . (R2Storage::configured() ? 'YES' : 'NO') . PHP_EOL;
echo 'Mode:          ' . ($dryRun ? 'DRY RUN (no changes)' : 'APPLY') . PHP_EOL . PHP_EOL;

if (!R2Storage::configured()) {
    echo "Nothing to do - R2 is not configured.\n";
    exit(1);
}

$rows = DB::table('files')
    ->where('stored_name', 'not like', 'r2:%')
    ->orderBy('id')
    ->get();

if ($rows->isEmpty()) {
    echo "Nothing to do - every file is already in R2.\n";
    exit(0);
}

$ok = $failed = 0;

foreach ($rows as $row) {
    $id = (int) $row->id;
    $key = ltrim(str_replace('\\', '/', (string) $row->stored_name), '/');
    printf("#%d %-20s %-46s %s\n", $id, $row->entity_type, $key, format_bytes((int) $row->size_bytes));

    // path() self-heals a local copy out of file_data when the disk copy is
    // missing, which is exactly the file we need to re-upload.
    $path = FileStorage::path((array) $row);

    if (!is_file($path)) {
        echo "    SKIP: no source bytes on disk and file_data is empty\n";
        $failed++;
        continue;
    }

    if ($dryRun) {
        echo '    dry-run: would upload from ' . $path . PHP_EOL;
        $ok++;
        continue;
    }

    try {
        R2Storage::put($key, $path, (string) $row->mime_type);
    } catch (Throwable $e) {
        echo '    PUT failed: ' . $e->getMessage() . PHP_EOL;
        $failed++;
        continue;
    }

    // Verify the object reads back before touching the row.
    try {
        $url = R2Storage::presignedGet($key);
    } catch (Throwable $e) {
        echo '    presign failed: ' . $e->getMessage() . PHP_EOL;
        $failed++;
        continue;
    }

    $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 60]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m) === 1) {
            $status = (int) $m[1];
        }
    }

    $expected = (int) $row->size_bytes;
    $got = $body === false ? -1 : strlen((string) $body);

    if ($status !== 200 || $got !== $expected) {
        printf("    VERIFY FAILED: HTTP %d, got %d bytes, expected %d\n", $status, $got, $expected);
        echo "    row left untouched\n";
        $failed++;
        continue;
    }

    echo "    verified: HTTP 200, $got bytes read back from R2\n";

    DB::table('files')->where('id', $id)->update([
        'stored_name' => R2Storage::PREFIX . $key,
        'file_data' => null,
    ]);

    @unlink($path);
    echo '    migrated to r2: prefix, file_data cleared' . PHP_EOL;
    $ok++;
}

echo PHP_EOL;
printf("done: %d migrated, %d failed\n", $ok, $failed);
exit($failed > 0 ? 1 : 0);

function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $value = (float) $bytes;

    while ($value >= 1024 && $i < count($units) - 1) {
        $value /= 1024;
        $i++;
    }

    return sprintf('%.1f %s', $value, $units[$i]);
}
