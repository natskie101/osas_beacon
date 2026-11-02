<?php
/** @var int $status */
/** @var string $message */
/** @var bool $debug */
/** @var Throwable|null $exception */
$title = match ($status) {
    403 => 'Access Restricted',
    404 => 'Page Not Found',
    419 => 'Session Expired',
    429 => 'Too Many Requests',
    default => 'Something Went Wrong',
};
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · BEACON OSAS Portal</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app-php.css') ?>">
</head>
<body data-page="error">
<main class="error-page">
    <div class="card error-card">
        <div class="error-card__code"><?= (int) $status ?></div>
        <h1><?= e($title) ?></h1>
        <p><?= e($message) ?></p>

        <?php if ($debug && $exception instanceof Throwable): ?>
            <details class="error-card__debug">
                <summary>Debug details</summary>
                <pre><?= e(get_class($exception) . ': ' . $exception->getMessage() . "\n\n" . $exception->getTraceAsString()) ?></pre>
            </details>
        <?php endif; ?>

        <div class="error-card__actions">
            <a href="<?= url('/') ?>" class="btn btn--primary">Back to Portal</a>
            <a href="<?= url('/login') ?>" class="btn btn--outline">Sign In</a>
        </div>
    </div>
</main>
</body>
</html>
