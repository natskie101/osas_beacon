<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/console.php';

use Database\Seeders\DatabaseSeeder;

$fresh = in_array('--fresh', $argv ?? [], true);

try {
    (new DatabaseSeeder())->run($fresh);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Seeding failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
