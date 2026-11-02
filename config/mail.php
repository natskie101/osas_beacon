<?php

declare(strict_types=1);

return [
    'mailer' => env('MAIL_MAILER', 'log'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'osas.support@uc.edu.ph'),
    'from_name' => env('MAIL_FROM_NAME', 'BEACON OSAS Portal'),
    'log_path' => BASE_PATH . '/storage/logs/mail.log',
];
