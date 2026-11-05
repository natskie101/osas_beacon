<?php
/** @var array<string, mixed>|null $target */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
/** @var list<string> $roles */
/** @var bool $canChangeRole */
/** @var list<string> $departments */
/** @var list<string> $programs */
/** @var list<string> $yearLevels */
/** @var list<string> $accountTiers */
/** @var list<string> $statuses */
$isEdit = $target !== null;
$roleLocked = !$canChangeRole || count($roles) <= 1;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$selected = static fn (string $value, ?string $current): string => (string) $value === (string) $current ? ' selected' : '';
$action = $isEdit ? url('/admin/users/' . $target['public_id']) : url('/admin/users');
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/admin/users') ?>">User Management</a> / <?= $isEdit ? 'Edit' : 'Create' ?></div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
    <div class="page-head__actions">
        <a href="<?= $isEdit ? url('/admin/users/' . $target['public_id']) : url('/admin/users') ?>" class="btn btn--outline">Cancel</a>
    </div>
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

<form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data" class="card form-card" novalidate>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('user', 14) ?> Account Identity</div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="first_name">First name <span class="req">*</span></label>
                <input class="input<?= $invalid('first_name') ?>" id="first_name" name="first_name" value="<?= e($values['first_name'] ?? '') ?>" required>
                <?php if ($errorFor('first_name')): ?><div class="form-error"><?= e($errorFor('first_name')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="middle_name">Middle name</label>
                <input class="input<?= $invalid('middle_name') ?>" id="middle_name" name="middle_name" value="<?= e($values['middle_name'] ?? '') ?>" maxlength="100">
                <?php if ($errorFor('middle_name')): ?><div class="form-error"><?= e($errorFor('middle_name')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="last_name">Last name <span class="req">*</span></label>
                <input class="input<?= $invalid('last_name') ?>" id="last_name" name="last_name" value="<?= e($values['last_name'] ?? '') ?>" required>
                <?php if ($errorFor('last_name')): ?><div class="form-error"><?= e($errorFor('last_name')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="email">University email <span class="req">*</span></label>
                <input class="input<?= $invalid('email') ?>" id="email" name="email" type="email" value="<?= e($values['email'] ?? '') ?>" required>
                <?php if ($errorFor('email')): ?><div class="form-error"><?= e($errorFor('email')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="username">Username</label>
                <input class="input<?= $invalid('username') ?>" id="username" name="username" value="<?= e($values['username'] ?? '') ?>" placeholder="Auto-generated from email when blank">
                <?php if ($errorFor('username')): ?><div class="form-error"><?= e($errorFor('username')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="phone">Phone number</label>
                <input class="input<?= $invalid('phone') ?>" id="phone" name="phone" value="<?= e($values['phone'] ?? '') ?>" placeholder="+63 9XX XXX XXXX">
                <?php if ($errorFor('phone')): ?><div class="form-error"><?= e($errorFor('phone')) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('shield', 14) ?> Access & Role</div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="role">Role <span class="req">*</span></label>
                <?php if ($roleLocked): ?>
                    <input type="hidden" name="role" value="<?= e($roles[0] ?? 'Student') ?>">
                    <input class="input" id="role" value="<?= e($roles[0] ?? 'Student') ?>" disabled>
                    <div class="help-text">Your role can only create accounts at this access level.</div>
                <?php else: ?>
                    <select class="input<?= $invalid('role') ?>" id="role" name="role" data-role-select>
                        <?php foreach ($roles as $roleOption): ?>
                            <option value="<?= e($roleOption) ?>"<?= $selected($roleOption, $values['role'] ?? 'Student') ?>><?= e($roleOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <?php if ($errorFor('role')): ?><div class="form-error"><?= e($errorFor('role')) ?></div><?php endif; ?>
            </div>

            <div class="field">
                <label for="status">Account status <span class="req">*</span></label>
                <select class="input<?= $invalid('status') ?>" id="status" name="status">
                    <?php foreach ($statuses as $statusOption): ?>
                        <option value="<?= e($statusOption) ?>"<?= $selected($statusOption, $values['status'] ?? 'Pending') ?>><?= e($statusOption) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('status')): ?><div class="form-error"><?= e($errorFor('status')) ?></div><?php endif; ?>
            </div>

            <div class="field">
                <label for="access_level">Access level</label>
                <input class="input<?= $invalid('access_level') ?>" id="access_level" name="access_level" value="<?= e($values['access_level'] ?? '') ?>" data-access-level readonly>
                <div class="help-text">Derived from the selected role.</div>
                <?php if ($errorFor('access_level')): ?><div class="form-error"><?= e($errorFor('access_level')) ?></div><?php endif; ?>
            </div>

            <div class="field">
                <label for="account_tier">Account tier <span class="req">*</span></label>
                <select class="input<?= $invalid('account_tier') ?>" id="account_tier" name="account_tier" data-account-tier>
                    <?php foreach ($accountTiers as $tier): ?>
                        <option value="<?= e($tier) ?>"<?= $selected($tier, $values['account_tier'] ?? 'Student Tier') ?>><?= e($tier) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('account_tier')): ?><div class="form-error"><?= e($errorFor('account_tier')) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section" data-student-fields>
        <div class="section-title"><?= App\Support\Icons::render('graduation', 14) ?> Student Information</div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="student_id">Student ID</label>
                <input class="input<?= $invalid('student_id') ?>" id="student_id" name="student_id" value="<?= e($values['student_id'] ?? '') ?>" placeholder="22-00104">
                <?php if ($errorFor('student_id')): ?><div class="form-error"><?= e($errorFor('student_id')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="age">Age</label>
                <input class="input<?= $invalid('age') ?>" id="age" name="age" type="number" min="10" max="100" value="<?= e($values['age'] ?? '') ?>">
                <?php if ($errorFor('age')): ?><div class="form-error"><?= e($errorFor('age')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="department">Department / College</label>
                <input class="input<?= $invalid('department') ?>" id="department" name="department" list="department-options" value="<?= e($values['department'] ?? '') ?>">
                <datalist id="department-options">
                    <?php foreach ($departments as $department): ?><option value="<?= e($department) ?>"><?php endforeach; ?>
                </datalist>
                <?php if ($errorFor('department')): ?><div class="form-error"><?= e($errorFor('department')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="program">Program</label>
                <input class="input<?= $invalid('program') ?>" id="program" name="program" list="program-options" value="<?= e($values['program'] ?? '') ?>">
                <datalist id="program-options">
                    <?php foreach ($programs as $program): ?><option value="<?= e($program) ?>"><?php endforeach; ?>
                </datalist>
                <?php if ($errorFor('program')): ?><div class="form-error"><?= e($errorFor('program')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="year_level">Year level</label>
                <select class="input<?= $invalid('year_level') ?>" id="year_level" name="year_level">
                    <option value="">Not applicable</option>
                    <?php foreach ($yearLevels as $year): ?>
                        <option value="<?= e($year) ?>"<?= $selected($year, $values['year_level'] ?? '') ?>><?= e($year) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('year_level')): ?><div class="form-error"><?= e($errorFor('year_level')) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('lock', 14) ?> Account Password</div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="password"><?= $isEdit ? 'New password (optional)' : 'Password' ?> <?= $isEdit ? '' : '<span class="req">*</span>' ?></label>
                <input class="input<?= $invalid('password') ?>" id="password" name="password" type="password" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?> placeholder="<?= $isEdit ? 'Leave blank to keep the current password' : 'At least 8 characters' ?>">
                <?php if ($errorFor('password')): ?><div class="form-error"><?= e($errorFor('password')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter the password">
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('upload', 14) ?> School ID Upload</div>
        <div class="field">
            <label for="school_id">School ID document</label>
            <input class="input<?= $invalid('school_id') ?>" id="school_id" name="school_id" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
            <div class="help-text">PDF, JPG or PNG up to 5 MB. The file is stored outside the public web root and is only streamed to authorised staff.</div>
            <?php if ($errorFor('school_id')): ?><div class="form-error"><?= e($errorFor('school_id')) ?></div><?php endif; ?>
            <?php if ($isEdit && !empty($values['school_id_file_id'])): ?>
                <div class="cell-sub">A document is already attached. Uploading a new file replaces it.</div>
            <?php endif; ?>
        </div>
    </section>

    <div class="form-actions">
        <a href="<?= $isEdit ? url('/admin/users/' . $target['public_id']) : url('/admin/users') ?>" class="btn btn--ghost">Cancel</a>
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Save Changes' : 'Create Account' ?></button>
    </div>
</form>
