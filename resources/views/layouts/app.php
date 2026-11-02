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
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'OSAS Portal') ?> · BEACON OSAS Portal</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app-php.css') ?>">
</head>
<body data-page="<?= e($pageKey ?? '') ?>">
<div class="app">
    <?= App\Support\View::render('partials/sidebar', compact('navItems', 'pageKey', 'portalLabel', 'user', 'initials')) ?>
    <div class="main">
        <?= App\Support\View::render('partials/topbar', compact('pageTitle', 'pageSubtitle', 'user', 'role', 'initials')) ?>
        <main class="content" id="page-content">
            <?= App\Support\View::render('partials/flash', [
                'status' => App\Support\Session::getFlash('status'),
                'error' => App\Support\Session::getFlash('error'),
            ]) ?>
            <?= $content ?>
        </main>
        <footer class="app-foot">
            <span>BEACON OSAS Portal &middot; <?= e(config('app.institution.department')) ?></span>
            <span><?= e(config('app.institution.name')) ?> &middot; <?= e(config('app.institution.academic_year')) ?></span>
        </footer>
    </div>
</div>
<div id="modal-root" class="modal-root"></div>
<div id="toast-root" class="toast-root"></div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
