<?php

declare(strict_types=1);

return [
    'default' => env('MAIL_MAILER', env('APP_ENV') === 'production' ? 'smtp' : 'log'),
    'mailer' => env('MAIL_MAILER', env('APP_ENV') === 'production' ? 'smtp' : 'log'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'carlosnath991@gmail.com'),
        'name' => env('MAIL_FROM_NAME', 'BEACON OSAS Portal'),
    ],
    'from_address' => env('MAIL_FROM_ADDRESS', 'carlosnath991@gmail.com'),
    'from_name' => env('MAIL_FROM_NAME', 'BEACON OSAS Portal'),
    'log_path' => BASE_PATH . '/storage/logs/mail.log',

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp-relay.brevo.com'),
            'port' => (int) env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => 5,
            'auth_mode' => null,
        ],
        'log' => [
            'transport' => 'log',
            'path' => env('MAIL_LOG_PATH', BASE_PATH . '/storage/logs/mail.log'),
        ],
    ],
];
