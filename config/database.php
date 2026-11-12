<?php

declare(strict_types=1);

$driver = env('DB_CONNECTION', 'pgsql');
$host = env('DB_HOST', '127.0.0.1');
$port = (int) env('DB_PORT', 5432);
$database = env('DB_DATABASE', 'osas_beacon');
$username = env('DB_USERNAME', 'osas_beacon');
$password = env('DB_PASSWORD', '');

return [
    /* Top-level convenience keys consumed via config('database.driver') etc. */
    'default' => $driver,
    'driver' => $driver,
    'host' => $host,
    'port' => $port,
    'database' => $database,
    'username' => $username,
    'password' => $password,

    /* Laravel standard connection configurations */
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', dirname(__DIR__) . '/database/database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
];
