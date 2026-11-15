@extends('layouts.app')
@section('content')
<?php
/** @var array<string, string> $errors */
/** @var array<string, mixed> $values */
/** @var bool $isAdmin */
/** @var bool $isStudent */
/** @var list<string> $departments */
/** @var list<string> $programs */
/** @var list<string> $yearLevels */
/** @var array<string, mixed>|null $profilePhoto */
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow">Account</div>
        <h2 class="page-head__title">My Profile</h2>
        <p class="page-head__sub">Update your personal information, sign-in credentials, and profile photo.</p>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span>
        <div>
            <div class="alert__title">Please correct the highlighted fields</div>
            <div class="alert__text">{{ reset($errors) }}</div>
        </div>
    </div>
<?php endif; ?>

<?php if (session('profile_success')): ?>
    <div class="alert alert--success" role="status">
        <span class="alert__ico">{!! App\Support\Icons::render('checkCircle', 18) !!}</span>
        <div>
            <div class="alert__title">Success</div>
            <div class="alert__text">{{ session('profile_success') }}</div>
        </div>
    </div>
<?php endif; ?>

<form class="card profile-form" method="POST" action="{!! url('/account/profile') !!}" enctype="multipart/form-data" novalidate>
    {!! csrf_field() !!}
    {!! method_field('PUT') !!}

    <?php if (!$isAdmin): ?>
    <section class="form-section">
        <div class="section-title">{!! App\Support\Icons::render('camera', 14) !!} Profile Photo</div>
        <div class="profile-photo-row">
            <div class="profile-photo-avatar">
                <?php if ($profilePhoto !== null): ?>
                    <img src="{!! url('/account/profile/photo/' . (int) $profilePhoto['id']) !!}" alt="Profile photo">
                <?php else: ?>
                    <span class="avatar avatar--blue avatar--lg">{{ initials($values['first_name'] ?? '', $values['last_name'] ?? '') }}</span>
                <?php endif; ?>
            </div>
            <div class="profile-photo-controls">
                <input class="input" id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                <div class="help-text">Upload a clear square photo (JPG or PNG, up to 5 MB).</div>
                <?php if ($errorFor('profile_photo')): ?><div class="form-error">{{ $errorFor('profile_photo') }}</div><?php endif; ?>
                <?php if ($profilePhoto !== null): ?>
                    <label class="profile-photo__remove"><input type="checkbox" name="remove_profile_photo" value="1"> Remove current photo</label>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <?php if ($isAdmin): ?>
    <section class="form-section">
        <div class="section-title">{!! App\Support\Icons::render('user', 14) !!} Basic Information</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="full_name">Full name</label><input class="input" id="full_name" value="{{ trim(($values['first_name'] ?? '') . ' ' . ($values['last_name'] ?? '')) }}" readonly></div>
            <div class="field"><label for="account_role">Role</label><input class="input" id="account_role" value="{{ $values['role'] ?? '' }}" readonly></div>
            <div class="field"><label for="account_username">Username</label><input class="input" id="account_username" value="{{ $values['username'] ?? '' }}" readonly></div>
        </div>
        <p class="help-text">Your name, role and username are managed by OSAS and cannot be changed from this page.</p>
    </section>
    <?php else: ?>
    <section class="form-section">
        <div class="section-title">{!! App\Support\Icons::render('user', 14) !!} Personal Information</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="first_name">First name <span class="req">*</span></label><input class="input{!! $invalid('first_name') !!}" id="first_name" name="first_name" value="{{ $values['first_name'] ?? '' }}" maxlength="80" required placeholder="e.g. Juan"><?php if ($errorFor('first_name')): ?><div class="form-error">{{ $errorFor('first_name') }}</div><?php endif; ?></div>
            <div class="field"><label for="middle_name">Middle name</label><input class="input{!! $invalid('middle_name') !!}" id="middle_name" name="middle_name" value="{{ $values['middle_name'] ?? '' }}" maxlength="100" placeholder="e.g. Santos (Optional)"><?php if ($errorFor('middle_name')): ?><div class="form-error">{{ $errorFor('middle_name') }}</div><?php endif; ?></div>
            <div class="field"><label for="last_name">Last name <span class="req">*</span></label><input class="input{!! $invalid('last_name') !!}" id="last_name" name="last_name" value="{{ $values['last_name'] ?? '' }}" maxlength="80" required placeholder="e.g. Dela Cruz"><?php if ($errorFor('last_name')): ?><div class="form-error">{{ $errorFor('last_name') }}</div><?php endif; ?></div>
            <div class="field"><label for="phone">Phone number</label><input class="input{!! $invalid('phone') !!}" id="phone" name="phone" value="{{ $values['phone'] ?? '' }}" maxlength="11" placeholder="09XXXXXXXXX"><?php if ($errorFor('phone')): ?><div class="form-error">{{ $errorFor('phone') }}</div><?php endif; ?></div>
        </div>
    </section>
    <?php endif; ?>
    <section class="form-section">
        <div class="section-title">{!! App\Support\Icons::render('lock', 14) !!} Sign-in Information</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="email">Email address <span class="req">*</span></label><input class="input{!! $invalid('email') !!}" id="email" name="email" type="email" value="{{ $values['email'] ?? '' }}" required><?php if ($errorFor('email')): ?><div class="form-error">{{ $errorFor('email') }}</div><?php endif; ?></div>
            <?php if (!$isAdmin): ?>
            <div class="field"><label for="username">Username <span class="req">*</span></label><input class="input{!! $invalid('username') !!}" id="username" name="username" value="{{ $values['username'] ?? '' }}" required><?php if ($errorFor('username')): ?><div class="form-error">{{ $errorFor('username') }}</div><?php endif; ?></div>
            <?php endif; ?>
        </div>
        <p class="help-text">Changing your email address requires your current password.</p>
    </section>
    <?php if (!$isAdmin && $isStudent): ?>
        <section class="form-section">
            <div class="section-title">{!! App\Support\Icons::render('graduation', 14) !!} Student Information</div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="student_id">Student ID</label><input class="input" id="student_id" value="{{ $values['student_id'] ?? '' }}" readonly><div class="help-text">Student ID cannot be changed from your profile.</div></div>
                <div class="field"><label for="age">Age <span class="req">*</span></label><input class="input{!! $invalid('age') !!}" id="age" name="age" type="number" min="10" max="100" value="{{ $values['age'] ?? '' }}" required><?php if ($errorFor('age')): ?><div class="form-error">{{ $errorFor('age') }}</div><?php endif; ?></div>
                <div class="field"><label for="department">Department</label><select class="input{!! $invalid('department') !!}" id="department" name="department"><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="{{ $department }}"{!! ($values['department'] ?? '') === $department ? ' selected' : '' !!}>{{ $department }}</option><?php endforeach; ?></select><?php if ($errorFor('department')): ?><div class="form-error">{{ $errorFor('department') }}</div><?php endif; ?></div>
                <div class="field"><label for="program">Program <span class="req">*</span></label><select class="input{!! $invalid('program') !!}" id="program" name="program" required><?php foreach ($programs as $program): ?><option value="{{ $program }}"{!! ($values['program'] ?? '') === $program ? ' selected' : '' !!}>{{ $program }}</option><?php endforeach; ?></select><?php if ($errorFor('program')): ?><div class="form-error">{{ $errorFor('program') }}</div><?php endif; ?></div>
                <div class="field"><label for="year_level">Year level <span class="req">*</span></label><select class="input{!! $invalid('year_level') !!}" id="year_level" name="year_level" required><?php foreach ($yearLevels as $yearLevel): ?><option value="{{ $yearLevel }}"{!! ($values['year_level'] ?? '') === $yearLevel ? ' selected' : '' !!}>{{ $yearLevel }}</option><?php endforeach; ?></select><?php if ($errorFor('year_level')): ?><div class="form-error">{{ $errorFor('year_level') }}</div><?php endif; ?></div>
            </div>
        </section>
    <?php elseif (!$isAdmin): ?>
        <section class="form-section">
            <div class="section-title">{!! App\Support\Icons::render('user', 14) !!} Work Information</div>
            <div class="field"><label for="department">Department</label><select class="input{!! $invalid('department') !!}" id="department" name="department"><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="{{ $department }}"{!! ($values['department'] ?? '') === $department ? ' selected' : '' !!}>{{ $department }}</option><?php endforeach; ?></select><?php if ($errorFor('department')): ?><div class="form-error">{{ $errorFor('department') }}</div><?php endif; ?></div>
        </section>
    <?php endif; ?>
    <section class="form-section">
        <div class="section-title">{!! App\Support\Icons::render('shield', 14) !!} Change Password</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="current_password">Current password</label><input class="input{!! $invalid('current_password') !!}" id="current_password" name="current_password" type="password" autocomplete="current-password"><?php if ($errorFor('current_password')): ?><div class="form-error">{{ $errorFor('current_password') }}</div><?php endif; ?><div class="help-text">Required when changing your email or password.</div></div>
            <div class="field"><label for="password">New password</label><input class="input{!! $invalid('password') !!}" id="password" name="password" type="password" minlength="12" autocomplete="new-password" placeholder="Leave blank to keep your current password"><?php if ($errorFor('password')): ?><div class="form-error">{{ $errorFor('password') }}</div><?php endif; ?></div>
            <div class="field"><label for="password_confirmation">Confirm new password</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
        </div>
    </section>
    <?php if (!$isAdmin): ?>
    <section class="form-section profile-form__verification">
        <div class="section-title">{!! App\Support\Icons::render('info', 14) !!} Verification Document</div>
        <p class="panel-card__text">Your submitted school ID picture cannot be edited or replaced from this page. Contact OSAS if it needs to be updated.</p>
    </section>
    <?php endif; ?>
    <div class="form-actions"><a class="btn btn--ghost" href="{!! url($isAdmin ? '/admin/system-overview' : ($isStudent ? '/student/dashboard' : '/admin/dashboard')) !!}">Cancel</a><button class="btn btn--primary" type="submit" data-confirm="Save changes to your profile?">Save Profile</button></div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var firstName = document.getElementById("first_name");
    var middleName = document.getElementById("middle_name");
    var lastName = document.getElementById("last_name");
    var phone = document.getElementById("phone");
    var age = document.getElementById("age");

    function restrictInput(element, regex) {
        if (!element) return;
        element.addEventListener("input", function (e) {
            var val = element.value;
            var filtered = "";
            for (var i = 0; i < val.length; i++) {
                if (regex.test(val[i])) {
                    filtered += val[i];
                }
            }
            if (val !== filtered) {
                element.value = filtered;
            }
        });
    }

    var letterRegex = /^[a-zA-ZñÑ\s\-\.]$/;
    if (firstName) restrictInput(firstName, letterRegex);
    if (middleName) restrictInput(middleName, letterRegex);
    if (lastName) restrictInput(lastName, letterRegex);

    var digitRegex = /^\d$/;
    if (phone) restrictInput(phone, digitRegex);
    if (age) restrictInput(age, digitRegex);
});
</script>

@endsection
