<?php

declare(strict_types=1);

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        // The portal's single guard. The "beacon" driver is registered in
        // AppServiceProvider and extends the native session guard so the
        // portal's remember-me cookie + Active-status gate keep working.
        'web' => [
            'driver' => 'beacon',
            'provider' => 'beacon',
        ],
    ],

    'providers' => [
        'beacon' => [
            'driver' => 'beacon',
        ],
    ],

    // Password resets use the portal's own flow, not the native Password
    // broker, so no broker entries are configured.
    'passwords' => [],

    'password_timeout' => 10800,

];
