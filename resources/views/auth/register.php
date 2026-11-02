<?php
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var list<string> $programs */
/** @var list<string> $yearLevels */
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$selected = static fn (string $value, ?string $current): string => (string) $value === (string) $current ? ' selected' : '';
?><div class="auth__header">
    <h1 class="auth__title">Create Student Account</h1>
    <p class="auth__subtitle">Submit your details and school ID for OSAS verification.</p>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span>
        <div>
            <div class="alert__title">Please correct the highlighted fields</div>
            <div class="alert__text"><?= e(reset($errors)) ?></div>
        </div>
    </div>
<?php endif; ?>

<div class="alert alert--info" role="note">
    <span class="alert__ico"><?= App\Support\Icons::render('info', 18) ?></span>
    <div><div class="alert__text">Your account stays pending until a coordinator verifies your school ID and details.</div></div>
</div>

<form class="auth__form-inner auth__register-form" method="POST" action="<?= url('/create-account') ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="form-grid form-grid--2">
        <div class="field">
            <label for="first_name">First name <span class="req">*</span></label>
            <input class="input<?= $invalid('first_name') ?>" id="first_name" name="first_name" value="<?= e($old['first_name'] ?? '') ?>" maxlength="80" required>
            <?php if ($errorFor('first_name')): ?><div class="form-error"><?= e($errorFor('first_name')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="middle_name">Middle name <span class="req">*</span></label>
            <input class="input<?= $invalid('middle_name') ?>" id="middle_name" name="middle_name" value="<?= e($old['middle_name'] ?? '') ?>" maxlength="100" required>
            <?php if ($errorFor('middle_name')): ?><div class="form-error"><?= e($errorFor('middle_name')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="last_name">Surname <span class="req">*</span></label>
            <input class="input<?= $invalid('last_name') ?>" id="last_name" name="last_name" value="<?= e($old['last_name'] ?? '') ?>" maxlength="80" required>
            <?php if ($errorFor('last_name')): ?><div class="form-error"><?= e($errorFor('last_name')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="student_id">Student ID <span class="req">*</span></label>
            <input class="input<?= $invalid('student_id') ?>" id="student_id" name="student_id" value="<?= e($old['student_id'] ?? '') ?>" maxlength="30" placeholder="22-00104" required>
            <?php if ($errorFor('student_id')): ?><div class="form-error"><?= e($errorFor('student_id')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="age">Age <span class="req">*</span></label>
            <input class="input<?= $invalid('age') ?>" id="age" name="age" type="number" min="10" max="100" value="<?= e($old['age'] ?? '') ?>" required>
            <?php if ($errorFor('age')): ?><div class="form-error"><?= e($errorFor('age')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="email">Email address <span class="req">*</span></label>
            <input class="input<?= $invalid('email') ?>" id="email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>" maxlength="190" placeholder="name@uc.edu.ph" autocomplete="email" required>
            <?php if ($errorFor('email')): ?><div class="form-error"><?= e($errorFor('email')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="program">Program / Course <span class="req">*</span></label>
            <select class="input<?= $invalid('program') ?>" id="program" name="program" required>
                <option value="">Select your program</option>
                <?php foreach ($programs as $program): ?>
                    <option value="<?= e($program) ?>"<?= $selected($program, $old['program'] ?? '') ?>><?= e($program) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($errorFor('program')): ?><div class="form-error"><?= e($errorFor('program')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="phone">Mobile number <span class="req">*</span></label>
            <input class="input<?= $invalid('phone') ?>" id="phone" name="phone" value="<?= e($old['phone'] ?? '') ?>" maxlength="30" placeholder="+63 9XX XXX XXXX" required>
            <?php if ($errorFor('phone')): ?><div class="form-error"><?= e($errorFor('phone')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="year_level">Year level <span class="req">*</span></label>
            <select class="input<?= $invalid('year_level') ?>" id="year_level" name="year_level" required>
                <option value="">Select your year level</option>
                <?php foreach ($yearLevels as $year): ?>
                    <option value="<?= e($year) ?>"<?= $selected($year, $old['year_level'] ?? '') ?>><?= e($year) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($errorFor('year_level')): ?><div class="form-error"><?= e($errorFor('year_level')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="password">Password <span class="req">*</span></label>
            <input class="input<?= $invalid('password') ?>" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            <div class="help-text">At least 8 characters.</div>
            <?php if ($errorFor('password')): ?><div class="form-error"><?= e($errorFor('password')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm password <span class="req">*</span></label>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
        </div>

        <div class="field">
            <label for="school_id">School ID verification <span class="req">*</span></label>
            <input class="input<?= $invalid('school_id') ?>" id="school_id" name="school_id" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required>
            <div class="help-text">JPG, PNG or PDF, up to 5 MB. Stored securely and reviewed by OSAS.</div>
            <?php if ($errorFor('school_id')): ?><div class="form-error"><?= e($errorFor('school_id')) ?></div><?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn--primary btn--block auth__submit">Submit for Verification</button>
</form>

<div class="auth__alt">
    <div class="auth__alt-row">
        <span class="auth__alt-line"></span><span>Already have an account?</span><span class="auth__alt-line"></span>
    </div>
    <a href="<?= url('/login') ?>" class="btn btn--outline btn--block">Back to Sign In</a>
</div>
