<?php

declare(strict_types=1);

return [
    'roles' => ['Coordinator', 'Administrator', 'Student'],

    'departments' => [
        'College of Computer Studies',
        'College of Business Administration',
        'College of Education',
        'College of Engineering',
        'College of Nursing',
        'College of Arts and Sciences',
        'Information Systems Office',
    ],

    'programs' => [
        'BS Information Technology',
        'BS Criminology',
        'BS Business Administration',
        'BS Hospitality Management',
        'BS Education',
        'BS Tourism Management',
        'BS Midwifery',
        'AB Real Estate Management',
    ],

    'year_levels' => ['First Year', 'Second Year', 'Third Year', 'Fourth Year'],

    'access_levels' => [
        'Student' => 'Student — Portal Access',
        'Administrator' => 'Administrator — Technical Support Access',
        'Coordinator' => 'Coordinator — Coordinator Portal Access',
    ],

    'statuses' => ['Active', 'Pending', 'Inactive'],
];
