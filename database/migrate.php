<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/console.php';

use Illuminate\Support\Facades\DB;

$driver = (string) config('database.driver', 'mysql');

if ($driver === 'mysql') {
    $host = (string) config('database.host', '127.0.0.1');
    $port = (int) config('database.port', 3306);
    $database = (string) config('database.database', 'osas_beacon');
    $username = (string) config('database.username', 'root');
    $password = (string) config('database.password', '');

    try {
        $server = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $database) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        echo "Database `{$database}` is ready.\n";
    } catch (PDOException $exception) {
        fwrite(STDERR, 'Could not create database: ' . $exception->getMessage() . "\n");
        exit(1);
    }
}

DB::statement(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration VARCHAR(190) NOT NULL,
        applied_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY schema_migrations_name_unique (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
);

$applied = DB::table('schema_migrations')
    ->orderBy('migration')
    ->pluck('migration')
    ->map(static fn (mixed $migration): string => (string) $migration)
    ->all();

$files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        echo "Skip  {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);

    if ($sql === false) {
        fwrite(STDERR, "Could not read {$name}\n");
        exit(1);
    }

    try {
        DB::unprepared($sql);
        DB::table('schema_migrations')->insert([
            'migration' => $name,
            'applied_at' => date('Y-m-d H:i:s'),
        ]);
        echo "Apply {$name}\n";
    } catch (Throwable $exception) {
        fwrite(STDERR, "Failed {$name}: " . $exception->getMessage() . "\n");
        exit(1);
    }
}

echo "Migrations complete.\n";
