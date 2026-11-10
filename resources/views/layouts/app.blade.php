<?php
/** @var array<string, mixed> $user */
/** @var string $role */
/** @var string $pageTitle */
/** @var string $pageSubtitle */
/** @var string $pageKey */
/** @var string $content */
$portal = $role === 'Student' ? 'student' : 'admin';
$navItems = config('navigation.' . $portal, []);

if ($portal === 'admin') {
    $navItems = array_values(array_filter(
        $navItems,
        static fn (array $item): bool => in_array($role, $item['roles'] ?? [], true),
    ));
}

$portalLabel = match ($role) {
    'Administrator' => 'Technical Console',
    'Student' => 'Student Portal',
    default => 'Coordinator Portal',
};
$initials = initials($user['first_name'] ?? '', $user['last_name'] ?? '');
$profilePhotoFile = $profilePhotoFile ?? (
    in_array($role, ['Coordinator', 'Student'], true)
        ? App\Models\File::latestForEntity('user_profile_photo', (string) ($user['id'] ?? ''))
        : null
);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'OSAS Portal' }} Â· BEACON OSAS Portal</title>
    <link rel="stylesheet" href="{!! asset('css/app.css') !!}?v={!! filemtime(BASE_PATH . '/public/assets/css/app.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/app-php.css') !!}?v={!! filemtime(BASE_PATH . '/public/assets/css/app-php.css') !!}">
    <?php if ($role === 'Administrator' && ($pageKey ?? '') === 'system-overview'): ?>
        <link rel="stylesheet" href="{!! asset('css/technical-admin.css') !!}?v={!! filemtime(BASE_PATH . '/public/assets/css/technical-admin.css') !!}">
    <?php endif; ?>
</head>
<body data-page="{{ $pageKey ?? '' }}" data-portal="{{ $portal }}" data-role="{{ $role }}">
<div class="app">
    @include('partials/sidebar', compact('navItems', 'pageKey', 'portalLabel', 'user', 'initials', 'profilePhotoFile'))
    <div class="main">
        @include('partials/topbar', compact('pageTitle', 'pageSubtitle', 'user', 'role', 'initials', 'profilePhotoFile'))
        <main class="content" id="page-content">
            @include('partials/flash', [
                'status' => session('status'),
                'error' => session('error'),
            ])
            <?php if ($role === 'Administrator' && ($pageKey ?? '') === 'system-overview'): ?>
                <div id="technical-overview">@yield('content')</div>
            <?php else: ?>
                @yield('content')
            <?php endif; ?>
        </main>
        <footer class="app-foot">
            <span>BEACON OSAS Portal &middot; {{ config('app.institution.department') }}</span>
            <span>{{ config('app.institution.name') }} &middot; {{ config('app.institution.academic_year') }}</span>
        </footer>
    </div>
</div>
<div id="modal-root" class="modal-root"></div>
<div id="toast-root" class="toast-root"></div>
<?php if ($role === 'Administrator' && ($pageKey ?? '') === 'system-overview'): ?>
    <script src="{!! asset('js/chart.umd.js') !!}?v={!! filemtime(BASE_PATH . '/public/assets/js/chart.umd.js') !!}"></script>
    <script src="{!! asset('js/technical-overview.js') !!}?v={!! filemtime(BASE_PATH . '/public/assets/js/technical-overview.js') !!}"></script>
<?php endif; ?>
<script src="{!! asset('js/app.js') !!}?v={!! filemtime(BASE_PATH . '/public/assets/js/app.js') !!}"></script>
</body>
</html>
