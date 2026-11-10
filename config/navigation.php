<?php

declare(strict_types=1);

return [
    'admin' => [
        ['label' => 'Dashboard', 'path' => '/admin/dashboard', 'page' => 'dashboard', 'icon' => 'dashboard', 'roles' => ['Coordinator']],
        ['label' => 'System Overview', 'path' => '/admin/system-overview', 'page' => 'system-overview', 'icon' => 'shield', 'roles' => ['Administrator']],
        ['label' => 'Announcements', 'path' => '/admin/announcements', 'page' => 'announcements', 'icon' => 'megaphone', 'roles' => ['Coordinator']],
        ['label' => 'Rules & Regulations', 'path' => '/admin/regulations', 'page' => 'regulations', 'icon' => 'scale', 'roles' => ['Coordinator']],
        ['label' => 'Orientation Content', 'path' => '/admin/orientation', 'page' => 'orientation', 'icon' => 'graduation', 'roles' => ['Coordinator']],
        ['label' => 'Events & Activities', 'path' => '/admin/events', 'page' => 'events', 'icon' => 'calendar', 'roles' => ['Coordinator']],
        ['label' => 'Lost & Found', 'path' => '/admin/lost-found', 'page' => 'lost-found', 'icon' => 'search', 'roles' => ['Coordinator']],
        ['label' => 'Document Requests', 'path' => '/admin/document-requests', 'page' => 'document-requests', 'icon' => 'printer', 'roles' => ['Coordinator']],
        ['label' => 'Viewing & Tracking', 'path' => '/admin/analytics', 'page' => 'analytics', 'icon' => 'activity', 'roles' => ['Coordinator']],
        ['label' => 'Support & Concerns', 'path' => '/admin/support', 'page' => 'support', 'icon' => 'headset', 'roles' => ['Coordinator', 'Administrator']],
        ['label' => 'User Management', 'path' => '/admin/users', 'page' => 'users', 'icon' => 'users', 'roles' => ['Coordinator', 'Administrator']],
        ['label' => 'Reports', 'path' => '/admin/reports', 'page' => 'reports', 'icon' => 'fileText', 'roles' => ['Coordinator']],
    ],

    'student' => [
        ['label' => 'Dashboard', 'path' => '/student/dashboard', 'page' => 'dashboard', 'icon' => 'dashboard'],
        ['label' => 'Announcements', 'path' => '/student/announcements', 'page' => 'announcements', 'icon' => 'megaphone'],
        ['label' => 'Rules and Regulations', 'path' => '/student/rules', 'page' => 'rules', 'icon' => 'scale'],
        ['label' => 'Orientations', 'path' => '/student/orientation', 'page' => 'orientation', 'icon' => 'graduation'],
        ['label' => 'Events and Activities', 'path' => '/student/events', 'page' => 'events', 'icon' => 'calendar'],
        ['label' => 'Lost and Found', 'path' => '/student/lost-found', 'page' => 'lost-found', 'icon' => 'search'],
        ['label' => 'Document Requests', 'path' => '/student/document-requests', 'page' => 'document-requests', 'icon' => 'printer'],
        ['label' => 'Feedback and Concerns', 'path' => '/student/support', 'page' => 'support', 'icon' => 'headset'],
    ],

    'meta' => [
        'admin' => [
            'dashboard' => ['title' => 'Admin Dashboard Overview', 'subtitle' => 'Consolidated real-time status of all OSAS portal modules, content operations and student support activity.'],
            'system-overview' => ['title' => 'System Overview', 'subtitle' => 'Review runtime health, sign-in activity, security signals, and top-performing portal content.'],
            'announcements' => ['title' => 'Announcements', 'subtitle' => 'Create, publish, and organize school announcements and information notices across the student portal.'],
            'regulations' => ['title' => 'Rules & Regulations', 'subtitle' => 'Maintain and communicate school policies, the student code of conduct, and mandated regulations.'],
            'orientation' => ['title' => 'Orientation', 'subtitle' => 'Manage onboarding materials, orientation modules, and information guides for incoming students.'],
            'events' => ['title' => 'Events & Activities', 'subtitle' => 'Schedule, publish and monitor university events, seminars, and student activities.'],
            'lost-found' => ['title' => 'Lost & Found', 'subtitle' => 'Review reported lost and found items, verify ownership claims, and record items returned to students.'],
            'document-requests' => ['title' => 'Document Requests', 'subtitle' => 'Process certificate and document requests submitted by students through the portal.'],
            'analytics' => ['title' => 'Viewing & Tracking', 'subtitle' => 'Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.'],
            'support' => ['title' => 'Support & Concerns', 'subtitle' => 'Manage student inquiries, complaints, and feedback, and monitor the OSAS service response performance.'],
            'users' => ['title' => 'User Account & Access Management', 'subtitle' => 'Manage portal access and system roles for all students and staff.'],
            'reports' => ['title' => 'Reports', 'subtitle' => 'Generate and export official reports for portal activity, compliance, and support operations.'],
            'activity' => ['title' => 'Audit Log', 'subtitle' => 'Review server-recorded account, content and support actions.'],
            'broadcasts' => ['title' => 'Broadcasts', 'subtitle' => 'Compose and deliver portal notifications to targeted student audiences.'],
        ],
        'student' => [
            'dashboard' => ['title' => 'Student Dashboard', 'subtitle' => 'Your OSAS portal overview, announcements and orientation progress.'],
            'rules' => ['title' => 'Rules and Regulations', 'subtitle' => 'The student code of conduct and campus policies that apply to you.'],
            'orientation' => ['title' => 'Orientation', 'subtitle' => 'Complete the mandatory onboarding modules for your cohort.'],
            'announcements' => ['title' => 'Announcements', 'subtitle' => 'Official notices and updates from the Office of Student Affairs and Services.'],
            'events' => ['title' => 'Events and Activities', 'subtitle' => 'University events, seminars and co-curricular activities.'],
            'lost-found' => ['title' => 'Lost and Found', 'subtitle' => 'Report a lost item, browse found items, and file an ownership claim.'],
            'document-requests' => ['title' => 'Document Requests', 'subtitle' => 'Request certificates and school documents from OSAS.'],
            'support' => ['title' => 'Feedback and Report', 'subtitle' => 'Submit a concern and follow the OSAS response thread.'],
        ],
    ],
];
