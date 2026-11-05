<?php
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
/** @var list<string> $departments */
/** @var list<string> $programs */
/** @var list<string> $yearLevels */
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$isStudent = $user['role'] === 'Student';
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">My Profile</h2>
        <p class="page-head__sub">Update your account information. Your role and account status are managed by OSAS.</p>
    </div>
</div>
<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span>
        <div><div class="alert__title">Please review your information</div><div class="alert__text"><?= e(reset($errors)) ?></div></div>
    </div>
<?php endif; ?>
<form class="card form-card profile-form" method="POST" action="<?= url('/profile') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('user', 14) ?> Personal Information</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="first_name">First name <span class="req">*</span></label><input class="input<?= $invalid('first_name') ?>" id="first_name" name="first_name" value="<?= e($values['first_name'] ?? '') ?>" required><?php if ($errorFor('first_name')): ?><div class="form-error"><?= e($errorFor('first_name')) ?></div><?php endif; ?></div>
            <div class="field"><label for="middle_name">Middle name</label><input class="input<?= $invalid('middle_name') ?>" id="middle_name" name="middle_name" value="<?= e($values['middle_name'] ?? '') ?>"><?php if ($errorFor('middle_name')): ?><div class="form-error"><?= e($errorFor('middle_name')) ?></div><?php endif; ?></div>
            <div class="field"><label for="last_name">Last name <span class="req">*</span></label><input class="input<?= $invalid('last_name') ?>" id="last_name" name="last_name" value="<?= e($values['last_name'] ?? '') ?>" required><?php if ($errorFor('last_name')): ?><div class="form-error"><?= e($errorFor('last_name')) ?></div><?php endif; ?></div>
            <div class="field"><label for="phone">Phone number</label><input class="input<?= $invalid('phone') ?>" id="phone" name="phone" value="<?= e($values['phone'] ?? '') ?>"><?php if ($errorFor('phone')): ?><div class="form-error"><?= e($errorFor('phone')) ?></div><?php endif; ?></div>
        </div>
    </section>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('lock', 14) ?> Sign-in Information</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="email">Email address <span class="req">*</span></label><input class="input<?= $invalid('email') ?>" id="email" name="email" type="email" value="<?= e($values['email'] ?? '') ?>" required><?php if ($errorFor('email')): ?><div class="form-error"><?= e($errorFor('email')) ?></div><?php endif; ?></div>
            <div class="field"><label for="username">Username <span class="req">*</span></label><input class="input<?= $invalid('username') ?>" id="username" name="username" value="<?= e($values['username'] ?? '') ?>" required><?php if ($errorFor('username')): ?><div class="form-error"><?= e($errorFor('username')) ?></div><?php endif; ?></div>
        </div>
        <p class="help-text">Changing your email address requires your current password.</p>
    </section>
    <?php if ($isStudent): ?>
        <section class="form-section">
            <div class="section-title"><?= App\Support\Icons::render('graduation', 14) ?> Student Information</div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="student_id">Student ID</label><input class="input" id="student_id" value="<?= e($values['student_id'] ?? '') ?>" readonly><div class="help-text">Student ID cannot be changed from your profile.</div></div>
                <div class="field"><label for="age">Age <span class="req">*</span></label><input class="input<?= $invalid('age') ?>" id="age" name="age" type="number" min="10" max="100" value="<?= e($values['age'] ?? '') ?>" required><?php if ($errorFor('age')): ?><div class="form-error"><?= e($errorFor('age')) ?></div><?php endif; ?></div>
                <div class="field"><label for="department">Department</label><select class="input<?= $invalid('department') ?>" id="department" name="department"><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= e($department) ?>"<?= ($values['department'] ?? '') === $department ? ' selected' : '' ?>><?= e($department) ?></option><?php endforeach; ?></select><?php if ($errorFor('department')): ?><div class="form-error"><?= e($errorFor('department')) ?></div><?php endif; ?></div>
                <div class="field"><label for="program">Program <span class="req">*</span></label><select class="input<?= $invalid('program') ?>" id="program" name="program" required><?php foreach ($programs as $program): ?><option value="<?= e($program) ?>"<?= ($values['program'] ?? '') === $program ? ' selected' : '' ?>><?= e($program) ?></option><?php endforeach; ?></select><?php if ($errorFor('program')): ?><div class="form-error"><?= e($errorFor('program')) ?></div><?php endif; ?></div>
                <div class="field"><label for="year_level">Year level <span class="req">*</span></label><select class="input<?= $invalid('year_level') ?>" id="year_level" name="year_level" required><?php foreach ($yearLevels as $yearLevel): ?><option value="<?= e($yearLevel) ?>"<?= ($values['year_level'] ?? '') === $yearLevel ? ' selected' : '' ?>><?= e($yearLevel) ?></option><?php endforeach; ?></select><?php if ($errorFor('year_level')): ?><div class="form-error"><?= e($errorFor('year_level')) ?></div><?php endif; ?></div>
            </div>
        </section>
    <?php else: ?>
        <section class="form-section">
            <div class="section-title"><?= App\Support\Icons::render('user', 14) ?> Work Information</div>
            <div class="field"><label for="department">Department</label><select class="input<?= $invalid('department') ?>" id="department" name="department"><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= e($department) ?>"<?= ($values['department'] ?? '') === $department ? ' selected' : '' ?>><?= e($department) ?></option><?php endforeach; ?></select><?php if ($errorFor('department')): ?><div class="form-error"><?= e($errorFor('department')) ?></div><?php endif; ?></div>
        </section>
    <?php endif; ?>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('shield', 14) ?> Change Password</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="current_password">Current password</label><input class="input<?= $invalid('current_password') ?>" id="current_password" name="current_password" type="password" autocomplete="current-password"><?php if ($errorFor('current_password')): ?><div class="form-error"><?= e($errorFor('current_password')) ?></div><?php endif; ?><div class="help-text">Required when changing your email or password.</div></div>
            <div class="field"><label for="password">New password</label><input class="input<?= $invalid('password') ?>" id="password" name="password" type="password" autocomplete="new-password" placeholder="Leave blank to keep your current password"><?php if ($errorFor('password')): ?><div class="form-error"><?= e($errorFor('password')) ?></div><?php endif; ?></div>
            <div class="field"><label for="password_confirmation">Confirm new password</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
        </div>
    </section>
    <section class="form-section profile-form__verification">
        <div class="section-title"><?= App\Support\Icons::render('info', 14) ?> Verification Document</div>
        <p class="panel-card__text">Your submitted school ID picture cannot be edited or replaced from this page. Contact OSAS if it needs to be updated.</p>
    </section>
    <div class="form-actions"><a class="btn btn--ghost" href="<?= url($isStudent ? '/student/dashboard' : '/admin/dashboard') ?>">Cancel</a><button class="btn btn--primary" type="submit" data-confirm="Save changes to your profile?">Save Profile</button></div>
</form>
