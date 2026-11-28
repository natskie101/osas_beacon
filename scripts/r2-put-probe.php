<?php

declare(strict_types=1);

/**
 * Surfaces the real failure behind R2Storage::put().
 *
 * FileStorage::storeUpload() catches RuntimeException silently, so uploads fall
 * back to base64-in-DB with no trace. This sends one real PUT with a tiny
 * object and prints the raw error, then removes the object again.
 */

$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\R2Storage;

// Optional overrides: php scripts/r2-put-probe.php <localPath> <key> <mime>
$seed = $argv[1] ?? null;
$key = $argv[2] ?? null;
$mime = $argv[3] ?? null;

if ($seed === null) {
    $seed = $base . '/storage/uploads/probe/avatar.png';
}

if (!is_file($seed)) {
    // Build a tiny PNG if the old probe source is gone.
    $seed = sys_get_temp_dir() . '/r2-probe.png';
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
    file_put_contents($seed, (string) $png);
}

if ($mime === null) {
    $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($seed) ?: 'application/octet-stream';
}

echo 'configured(): ' . (R2Storage::configured() ? 'YES' : 'NO') . PHP_EOL;
echo 'seed:        ' . $seed . ' (' . filesize($seed) . ' bytes)' . PHP_EOL;
echo 'mime:        ' . $mime . PHP_EOL . PHP_EOL;

$key ??= 'diagnostics/put-probe-' . time() . '.png';

try {
    R2Storage::put($key, $seed, $mime);
    echo 'PUT SUCCEEDED  key=' . $key . PHP_EOL;

    // Confirm it is really in the bucket via a signed GET.
    $url = R2Storage::presignedGet($key);
    $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 30]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m) === 1) {
            $status = (int) $m[1];
        }
    }
    echo 'signed GET after PUT: HTTP ' . ($status ?: 'no response')
        . ' (' . ($body === false ? 'empty' : strlen((string) $body) . ' bytes') . ')' . PHP_EOL;

    try {
        R2Storage::delete($key);
        echo 'cleanup: deleted probe object' . PHP_EOL;
    } catch (Throwable $e) {
        echo 'cleanup FAILED (object left behind): ' . $e->getMessage() . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'PUT FAILED' . PHP_EOL;
    echo '  class:   ' . get_class($e) . PHP_EOL;
    echo '  message: ' . $e->getMessage() . PHP_EOL;
}
