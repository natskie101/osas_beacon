<?php

declare(strict_types=1);

/**
 * Compare two model snapshots produced by scripts/model-snapshot.php.
 *
 * Usage:
 *   php scripts/model-snapshot-diff.php <before.json> <after.json>
 *
 * Exits 0 when every entry matches, 1 when any entry differs (the differing
 * keys and both values are printed).
 */

if ($argc !== 3) {
    fwrite(STDERR, 'Usage: php scripts/model-snapshot-diff.php <before.json> <after.json>' . PHP_EOL);
    exit(2);
}

$before = json_decode((string) file_get_contents($argv[1]), true);
$after = json_decode((string) file_get_contents($argv[2]), true);

if (!is_array($before) || !is_array($after)) {
    fwrite(STDERR, 'Failed to decode one of the snapshot files.' . PHP_EOL);
    exit(2);
}

$diffs = 0;

foreach ($before as $key => $value) {
    if (!array_key_exists($key, $after)) {
        echo "MISSING after: $key", PHP_EOL;
        $diffs++;
        continue;
    }

    if ($value !== $after[$key]) {
        echo "DIFF: $key", PHP_EOL;
        echo '  before: ', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
        echo '  after:  ', json_encode($after[$key], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
        $diffs++;
    }
}

foreach ($after as $key => $_) {
    if (!array_key_exists($key, $before)) {
        echo "NEW after: $key", PHP_EOL;
        $diffs++;
    }
}

if ($diffs === 0) {
    echo 'IDENTICAL (', count($before), ' entries)', PHP_EOL;
    exit(0);
}

echo "FAILED: $diffs differing entries", PHP_EOL;
exit(1);
