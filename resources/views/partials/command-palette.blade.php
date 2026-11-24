<?php
/** @var string $portal */
/** @var string $role */
$studentCommands = [
    ['title' => 'Dashboard', 'badge' => 'Home', 'url' => '/student/dashboard', 'icon' => 'home'],
    ['title' => 'Announcements', 'badge' => 'News', 'url' => '/student/announcements', 'icon' => 'megaphone'],
    ['title' => 'Orientation Modules', 'badge' => 'Compliance', 'url' => '/student/orientation', 'icon' => 'bookOpen'],
    ['title' => 'Support & Concerns', 'badge' => 'Helpdesk', 'url' => '/student/support', 'icon' => 'headset'],
    ['title' => 'Submit New Ticket', 'badge' => 'Action', 'url' => '/student/support/create', 'icon' => 'plus'],
    ['title' => 'Document Requests', 'badge' => 'Records', 'url' => '/student/document-requests', 'icon' => 'printer'],
    ['title' => 'Request a Document', 'badge' => 'Action', 'url' => '/student/document-requests/create', 'icon' => 'plus'],
    ['title' => 'Lost & Found Board', 'badge' => 'Campus', 'url' => '/student/lost-found', 'icon' => 'search'],
    ['title' => 'Report Lost / Found Item', 'badge' => 'Action', 'url' => '/student/lost-found/report', 'icon' => 'plus'],
    ['title' => 'Rules & Regulations', 'badge' => 'Handbook', 'url' => '/student/rules', 'icon' => 'scale'],
    ['title' => 'Campus Events', 'badge' => 'Activities', 'url' => '/student/events', 'icon' => 'calendar'],
    ['title' => 'My Profile & Account', 'badge' => 'Settings', 'url' => '/profile', 'icon' => 'user'],
    ['title' => 'Notifications', 'badge' => 'Alerts', 'url' => '/notifications', 'icon' => 'bell'],
];

$adminCommands = [
    ['title' => 'Dashboard Overview', 'badge' => 'Home', 'url' => '/admin/dashboard', 'icon' => 'home'],
    ['title' => 'User Directory', 'badge' => 'Accounts', 'url' => '/admin/users', 'icon' => 'users'],
    ['title' => 'Verification Queue', 'badge' => 'Pending', 'url' => '/admin/users?tab=queue', 'icon' => 'checkCircle'],
    ['title' => 'Create New User Account', 'badge' => 'Action', 'url' => '/admin/users/create', 'icon' => 'plus'],
    ['title' => 'Support & Concerns Desk', 'badge' => 'Helpdesk', 'url' => '/admin/support', 'icon' => 'headset'],
    ['title' => 'Document Requests Queue', 'badge' => 'Records', 'url' => '/admin/document-requests', 'icon' => 'printer'],
    ['title' => 'Announcements Manager', 'badge' => 'Broadcast', 'url' => '/admin/announcements', 'icon' => 'megaphone'],
    ['title' => 'Publish New Announcement', 'badge' => 'Action', 'url' => '/admin/announcements/create', 'icon' => 'plus'],
    ['title' => 'Campus Events & Activities', 'badge' => 'Calendar', 'url' => '/admin/events', 'icon' => 'calendar'],
    ['title' => 'Create New Event', 'badge' => 'Action', 'url' => '/admin/events/create', 'icon' => 'plus'],
    ['title' => 'Lost & Found Registry', 'badge' => 'Property', 'url' => '/admin/lost-found', 'icon' => 'search'],
    ['title' => 'Record Lost or Found Item', 'badge' => 'Action', 'url' => '/admin/lost-found/create', 'icon' => 'plus'],
    ['title' => 'Orientation Modules', 'badge' => 'Training', 'url' => '/admin/orientation', 'icon' => 'bookOpen'],
    ['title' => 'Add Orientation Module', 'badge' => 'Action', 'url' => '/admin/orientation/create', 'icon' => 'plus'],
    ['title' => 'Rules & Regulations', 'badge' => 'Policy', 'url' => '/admin/regulations', 'icon' => 'scale'],
    ['title' => 'Broadcast Notifications', 'badge' => 'Messaging', 'url' => '/admin/broadcasts', 'icon' => 'send'],
    ['title' => 'System Audit Log', 'badge' => 'Security', 'url' => '/admin/activity', 'icon' => 'shield'],
    ['title' => 'Operational Reports & Printouts', 'badge' => 'Reports', 'url' => '/admin/reports', 'icon' => 'fileText'],
    ['title' => 'My Profile & Security', 'badge' => 'Account', 'url' => '/profile', 'icon' => 'user'],
    ['title' => 'Notifications', 'badge' => 'Alerts', 'url' => '/notifications', 'icon' => 'bell'],
];

$commands = ($portal ?? '') === 'student' ? $studentCommands : $adminCommands;
?>
<div class="cmd-palette" id="cmd-palette" role="dialog" aria-modal="true" aria-label="Command Palette">
    <div class="cmd-palette__card">
        <div class="cmd-palette__input-wrap">
            {!! App\Support\Icons::render('search', 16) !!}
            <input type="text" class="cmd-palette__input" id="cmd-palette-input" placeholder="Type a destination or action..." autocomplete="off">
            <kbd class="cmd-palette__kbd">ESC</kbd>
        </div>
        <div class="cmd-palette__list" id="cmd-palette-list">
            <?php foreach ($commands as $cmd): ?>
                <a class="cmd-palette__item" href="{!! url($cmd['url']) !!}" data-cmd-title="{{ strtolower($cmd['title']) }}" data-cmd-badge="{{ strtolower($cmd['badge']) }}">
                    <span class="cmd-palette__item-icon">{!! App\Support\Icons::render($cmd['icon'], 16) !!}</span>
                    <span class="cmd-palette__item-title">{{ $cmd['title'] }}</span>
                    <span class="cmd-palette__item-badge">{{ $cmd['badge'] }}</span>
                </a>
            <?php endforeach; ?>
            <div class="cmd-palette__empty" id="cmd-palette-empty" hidden>No matching pages or actions found.</div>
        </div>
        <div class="cmd-palette__foot">
            <span>Navigation: <kbd class="cmd-palette__kbd">&uarr;</kbd> <kbd class="cmd-palette__kbd">&darr;</kbd> to browse, <kbd class="cmd-palette__kbd">&crarr;</kbd> to open</span>
            <span>OSAS BEACON</span>
        </div>
    </div>
</div>
