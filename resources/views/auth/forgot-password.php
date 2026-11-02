<?php
/** @var array<string, string> $errors */
/** @var string|null $status */
/** @var array<string, mixed> $old */
?>
<div class="auth__header">
    <h1 class="auth__title">Reset Portal Password</h1>
    <p class="auth__subtitle">Enter the university email address on your portal account.</p>
</div>

<?php if (!empty($status)): ?>
    <div class="alert alert--success" role="status">
        <span class="alert__ico"><?= App\Support\Icons::render('checkCircle', 18) ?></span>
        <div><div class="alert__text"><?= e($status) ?></div></div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span>
        <div><div class="alert__text"><?= e(reset($errors)) ?></div></div>
    </div>
<?php endif; ?>

<div class="alert alert--info" role="note">
    <span class="alert__ico"><?= App\Support\Icons::render('info', 18) ?></span>
    <div><div class="alert__text">Reset links expire after 15 minutes. Locally, the link is written to <code>storage/logs/mail.log</code> instead of being emailed.</div></div>
</div>

<form class="auth__form-inner" method="POST" action="<?= url('/forgot-password') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">University Email</label>
        <span class="input-group">
            <span class="input-group__ico" aria-hidden="true"><?= App\Support\Icons::render('mail', 16) ?></span>
            <input class="input" id="email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>" placeholder="name@uc.edu.ph" autocomplete="email" required>
        </span>
    </div>
    <button type="submit" class="btn btn--primary btn--block">Send Reset Link</button>
</form>

<div class="auth__alt">
    <a href="<?= url('/login') ?>" class="btn btn--outline btn--block">Back to Sign In</a>
</div>
