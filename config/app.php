<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'BEACON OSAS Portal'),
    'url' => env('APP_URL', ''),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),
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
