<?php

declare(strict_types=1);

$driver = env('DB_CONNECTION', 'mysql');
$host = env('DB_HOST', '127.0.0.1');
$port = (int) env('DB_PORT', 3306);
$database = env('DB_DATABASE', 'osas_beacon');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
$charset = 'utf8mb4';
$collation = 'utf8mb4_unicode_ci';

return [
    /* Top-level convenience keys consumed via config('database.driver') etc. */
    'default' => $driver,
    'driver' => $driver,
    'host' => $host,
    'port' => $port,
    'database' => $database,
    'username' => $username,
    'password' => $password,
    'charset' => $charset,
    'collation' => $collation,

    /* Laravel standard connection configurations */
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', dirname(__DIR__) . '/database/database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => $charset,
            'collation' => $collation,
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
    ],
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
];
