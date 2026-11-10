<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'BEACON OSAS Portal'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://127.0.0.1:8081'),
    'asset_url' => env('ASSET_URL', '/assets'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'demo_mode' => filter_var(env('APP_DEMO_MODE', false), FILTER_VALIDATE_BOOL),

    'institution' => [
        'name' => 'Southway College of Technology',
        'department' => 'Office of Student Affairs and Services',
        'academic_year' => 'AY 2026-2027',
        'support_email' => 'osas.support@uc.edu.ph',
    ],

    'session' => [
        'name' => env('SESSION_NAME', 'beacon_session'),
        'secure' => filter_var(env('SESSION_SECURE', false), FILTER_VALIDATE_BOOL),
        'lifetime' => 120 * 60,
    ],

    'remember' => [
        'cookie' => 'beacon_remember',
        'lifetime' => 60 * 60 * 24 * 30,
    ],
];
