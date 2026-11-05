<?php
/** @var string $title */
/** @var string $content */
/** @var bool|null $authSingle */
$authSingle = $authSingle ?? false;
$taglines = [
    'Information Broadcasting & Student Services System for Southway College of Technology.',
    'One portal for announcements, orientation, events and student support.',
    'Secure, auditable access for coordinators, administrators and students.',
];
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Portal Sign-In') ?> · BEACON OSAS Portal</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app-php.css') ?>">
</head>
<body data-page="<?= $authSingle ? 'register' : 'login' ?>">
<div id="shell"></div>
<main class="auth" id="auth-main">
    <div class="auth__card<?= $authSingle ? ' auth__card--single' : '' ?>">
        <?php if (!$authSingle): ?>
            <div class="auth__panel auth__left">
                <div class="auth__brand" id="auth-brand">
                    <img class="auth__logo" src="<?= asset('logo/5125ce0d-87ff-402c-a1b4-d5c370f44713.png') ?>" alt="BEACON" width="337" height="205">
                    <hr class="auth__rule">
                    <p class="auth__tag" id="auth-tag"><?= e($taglines[0]) ?></p>
                </div>
                <div class="auth__dots" id="auth-dots" role="tablist" aria-label="Feature highlights">
                    <?php foreach ($taglines as $index => $tagline): ?>
                        <button type="button"
                                class="pdot<?= $index === 0 ? ' pdot--on' : '' ?>"
                                data-tagline="<?= e($tagline) ?>"
                                aria-label="Slide <?= $index + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="auth__panel auth__right">
            <div class="auth__form<?= $authSingle ? ' auth__form--wide' : '' ?>">
                <?= $content ?>
                <p class="auth__legal">
                    Protected by BEACON Security Policy &bull; Authorized Access Only
                </p>
            </div>
        </div>
    </div>
</main>
<div id="toast-root" class="toast-root"></div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
