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
        'BS Computer Science',
        'BS Business Administration',
        'BS Accountancy',
        'BS Education',
        'BS Civil Engineering',
        'BS Nursing',
        'AB Communication',
    ],

    'year_levels' => ['First Year', 'Second Year', 'Third Year', 'Fourth Year'],

    'account_tiers' => ['Student Tier', 'Admin Tier'],

    'access_levels' => [
        'Student' => 'Student — Portal Access',
        'Administrator' => 'Administrator — Technical Support Access',
        'Coordinator' => 'Coordinator — Full System Access',
    ],

    'statuses' => ['Active', 'Pending', 'Inactive'],
];
