@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed>|null $target */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
/** @var list<string> $roles */
/** @var bool $canChangeRole */
/** @var list<string> $departments */
/** @var list<string> $programs */
/** @var list<string> $yearLevels */
/** @var list<string> $statuses */
$isEdit = $target !== null;
$roleLocked = !$canChangeRole || count($roles) <= 1;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$selected = static fn (string $value, ?string $current): string => (string) $value === (string) $current ? ' selected' : '';
$action = $isEdit ? url('/admin/users/' . $target['public_id']) : url('/admin/users');
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="{!! url('/admin/users') !!}">User Management</a> / {!! $isEdit ? 'Edit' : 'Create' !!}</div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
    <div class="page-head__actions">
        <a href="{!! $isEdit ? url('/admin/users/' . $target['public_id']) : url('/admin/users') !!}" class="btn btn--outline">Cancel</a>
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

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card form-card" novalidate>
    {!! csrf_field() !!}
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <section class="form-section">
        <div class="section-title">{!! App\Support\Icons::render('user', 14) !!} Account Identity</div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="first_name">First name <span class="req">*</span></label>
                <input class="input{!! $invalid('first_name') !!}" id="first_name" name="first_name" value="{{ $values['first_name'] ?? '' }}" required>
                <?php if ($errorFor('first_name')): ?><div class="form-error">{{ $errorFor('first_name') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="middle_name">Middle name</label>
                <input class="input{!! $invalid('middle_name') !!}" id="middle_name" name="middle_name" value="{{ $values['middle_name'] ?? '' }}" maxlength="100">
                <?php if ($errorFor('middle_name')): ?><div class="form-error">{{ $errorFor('middle_name') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="last_name">Last name <span class="req">*</span></label>
                <input class="input{!! $invalid('last_name') !!}" id="last_name" name="last_name" value="{{ $values['last_name'] ?? '' }}" required>
                <?php if ($errorFor('last_name')): ?><div class="form-error">{{ $errorFor('last_name') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="email">Email <span class="req">*</span></label>
                <input class="input{!! $invalid('email') !!}" id="email" name="email" type="email" value="{{ $values['email'] ?? '' }}" required>
                <?php if ($errorFor('email')): ?><div class="form-error">{{ $errorFor('email') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="username">Username</label>
                <input class="input{!! $invalid('username') !!}" id="username" name="username" value="{{ $values['username'] ?? '' }}" placeholder="Auto-generated from email when blank">
                <?php if ($errorFor('username')): ?><div class="form-error">{{ $errorFor('username') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="phone">Phone number</label>
                <input class="input{!! $invalid('phone') !!}" id="phone" name="phone" value="{{ $values['phone'] ?? '' }}" placeholder="+63 9XX XXX XXXX">
                <?php if ($errorFor('phone')): ?><div class="form-error">{{ $errorFor('phone') }}</div><?php endif; ?>
            </div>
            <div class="field" data-coordinator-age-field{!! ($values['role'] ?? 'Student') !== 'Coordinator' ? ' hidden' : '' !!}>
                <label for="coordinator_age">Age</label>
                <input class="input{!! $invalid('age') !!}" id="coordinator_age" name="age" type="number" min="10" max="100" value="{{ $values['age'] ?? '' }}"{!! ($values['role'] ?? 'Student') !== 'Coordinator' ? ' disabled' : '' !!}>
                <?php if ($errorFor('age')): ?><div class="form-error">{{ $errorFor('age') }}</div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-title">{!! App\Support\Icons::render('shield', 14) !!} Access & Role</div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="role">Role <span class="req">*</span></label>
                <?php if ($roleLocked): ?>
                    <input type="hidden" name="role" value="{{ $roles[0] ?? 'Student' }}">
                    <input class="input" id="role" value="{{ $roles[0] ?? 'Student' }}" disabled>
                    <div class="help-text">Your role can only create accounts at this access level.</div>
                <?php else: ?>
                    <select class="input{!! $invalid('role') !!}" id="role" name="role" data-role-select>
                        <?php foreach ($roles as $roleOption): ?>
                            <option value="{{ $roleOption }}"{!! $selected($roleOption, $values['role'] ?? 'Student') !!}>{{ $roleOption }}</option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <?php if ($errorFor('role')): ?><div class="form-error">{{ $errorFor('role') }}</div><?php endif; ?>
            </div>

            <div class="field">
                <label for="status">Account status <span class="req">*</span></label>
                <select class="input{!! $invalid('status') !!}" id="status" name="status">
                    <?php foreach ($statuses as $statusOption): ?>
                        <option value="{{ $statusOption }}"{!! $selected($statusOption, $values['status'] ?? 'Pending') !!}>{{ $statusOption }}</option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('status')): ?><div class="form-error">{{ $errorFor('status') }}</div><?php endif; ?>
            </div>

            <div class="field">
                <label for="access_level">Access level</label>
                <input class="input{!! $invalid('access_level') !!}" id="access_level" name="access_level" value="{{ $values['access_level'] ?? '' }}" data-access-level readonly>
                <div class="help-text">Derived from the selected role.</div>
                <?php if ($errorFor('access_level')): ?><div class="form-error">{{ $errorFor('access_level') }}</div><?php endif; ?>
            </div>

        </div>
    </section>

    <section class="form-section" data-student-fields{!! ($values['role'] ?? 'Student') !== 'Student' ? ' hidden' : '' !!}>
        <div class="section-title">{!! App\Support\Icons::render('graduation', 14) !!} Student Information</div>
        <div class="form-grid form-grid--3">
            <div class="field" data-student-id-field{!! ($values['role'] ?? 'Student') !== 'Student' ? ' hidden' : '' !!}>
                <label for="student_id">Student ID</label>
                <input class="input{!! $invalid('student_id') !!}" id="student_id" name="student_id" value="{{ $values['student_id'] ?? '' }}" placeholder="22-00104"{!! ($values['role'] ?? 'Student') !== 'Student' ? ' disabled' : '' !!}>
                <?php if ($errorFor('student_id')): ?><div class="form-error">{{ $errorFor('student_id') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="age">Age</label>
                <input class="input{!! $invalid('age') !!}" id="age" name="age" type="number" min="10" max="100" value="{{ $values['age'] ?? '' }}"{!! ($values['role'] ?? 'Student') !== 'Student' ? ' disabled' : '' !!}>
                <?php if ($errorFor('age')): ?><div class="form-error">{{ $errorFor('age') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="department">Department / College</label>
                <input class="input{!! $invalid('department') !!}" id="department" name="department" list="department-options" value="{{ $values['department'] ?? '' }}"{!! ($values['role'] ?? 'Student') !== 'Student' ? ' disabled' : '' !!}>
                <datalist id="department-options">
                    <?php foreach ($departments as $department): ?><option value="{{ $department }}"><?php endforeach; ?>
                </datalist>
                <?php if ($errorFor('department')): ?><div class="form-error">{{ $errorFor('department') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="program">Program</label>
                <input class="input{!! $invalid('program') !!}" id="program" name="program" list="program-options" value="{{ $values['program'] ?? '' }}"{!! ($values['role'] ?? 'Student') !== 'Student' ? ' disabled' : '' !!}>
                <datalist id="program-options">
                    <?php foreach ($programs as $program): ?><option value="{{ $program }}"><?php endforeach; ?>
                </datalist>
                <?php if ($errorFor('program')): ?><div class="form-error">{{ $errorFor('program') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="year_level">Year level</label>
                <select class="input{!! $invalid('year_level') !!}" id="year_level" name="year_level"{!! ($values['role'] ?? 'Student') !== 'Student' ? ' disabled' : '' !!}>
                    <option value="">Not applicable</option>
                    <?php foreach ($yearLevels as $year): ?>
                        <option value="{{ $year }}"{!! $selected($year, $values['year_level'] ?? '') !!}>{{ $year }}</option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('year_level')): ?><div class="form-error">{{ $errorFor('year_level') }}</div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-title">{!! App\Support\Icons::render('lock', 14) !!} Account Password</div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="password">{!! $isEdit ? 'New password (optional)' : 'Password' !!} {!! $isEdit ? '' : '<span class="req">*</span>' !!}</label>
                <input class="input{!! $invalid('password') !!}" id="password" name="password" type="password" autocomplete="new-password" {!! $isEdit ? '' : 'required' !!} placeholder="{!! $isEdit ? 'Leave blank to keep the current password' : 'At least 8 characters' !!}">
                <?php if ($errorFor('password')): ?><div class="form-error">{{ $errorFor('password') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter the password">
            </div>
        </div>
    </section>

    <section class="form-section" data-school-id-section{!! ($values['role'] ?? 'Student') !== 'Student' ? ' hidden' : '' !!}>
        <div class="section-title">{!! App\Support\Icons::render('upload', 14) !!} School ID Upload</div>
        <div class="field">
            <label for="school_id">School ID document</label>
            <input class="input{!! $invalid('school_id') !!}" id="school_id" name="school_id" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"{!! ($values['role'] ?? 'Student') !== 'Student' ? ' disabled' : '' !!}>
            <div class="help-text">PDF, JPG or PNG up to 5 MB. The file is stored outside the public web root and is only streamed to authorised staff.</div>
            <?php if ($errorFor('school_id')): ?><div class="form-error">{{ $errorFor('school_id') }}</div><?php endif; ?>
            <?php if ($isEdit && !empty($values['school_id_file_id'])): ?>
                <div class="cell-sub">A document is already attached. Uploading a new file replaces it.</div>
            <?php endif; ?>
        </div>
    </section>

    <div class="form-actions">
        <a href="{!! $isEdit ? url('/admin/users/' . $target['public_id']) : url('/admin/users') !!}" class="btn btn--ghost">Cancel</a>
        <button type="submit" class="btn btn--primary" {!! $isEdit ? 'data-confirm="Save changes to this account?"' : '' !!}>{!! $isEdit ? 'Save Changes' : 'Create Account' !!}</button>
    </div>
</form>

@endsection
