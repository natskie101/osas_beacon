@extends('layouts.auth')
@section('content')
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

<div id="register-alert-container">
<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span>
        <div>
            <div class="alert__title">Please correct the highlighted fields</div>
            <div class="alert__text">{{ reset($errors) }}</div>
        </div>
    </div>
<?php endif; ?>
</div>

<div class="alert alert--notice auth-register-notice" role="status">
    <span class="alert__ico">{!! App\Support\Icons::render('info', 18) !!}</span>
    <div class="alert__text">Your account stays pending until a coordinator verifies your school ID and details.</div>
</div>

<form id="register-form" class="auth__form-inner auth__register-form" method="POST" action="{!! url('/create-account') !!}" enctype="multipart/form-data" novalidate>
    {!! csrf_field() !!}

    <div class="form-grid form-grid--2">
        <div class="field">
            <label for="first_name">First name <span class="req">*</span></label>
            <input class="input{!! $invalid('first_name') !!}" id="first_name" name="first_name" value="{{ $old['first_name'] ?? '' }}" maxlength="80" required placeholder="e.g. Juan">
            <div class="form-error" id="error_first_name"><?php if ($errorFor('first_name')): ?>{{ $errorFor('first_name') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="middle_name">Middle name</label>
            <input class="input{!! $invalid('middle_name') !!}" id="middle_name" name="middle_name" value="{{ $old['middle_name'] ?? '' }}" maxlength="100" placeholder="e.g. Santos (Optional)">
            <div class="form-error" id="error_middle_name"><?php if ($errorFor('middle_name')): ?>{{ $errorFor('middle_name') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="last_name">Surname <span class="req">*</span></label>
            <input class="input{!! $invalid('last_name') !!}" id="last_name" name="last_name" value="{{ $old['last_name'] ?? '' }}" maxlength="80" required placeholder="e.g. Dela Cruz">
            <div class="form-error" id="error_last_name"><?php if ($errorFor('last_name')): ?>{{ $errorFor('last_name') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="student_id">Student ID <span class="req">*</span></label>
            <input class="input{!! $invalid('student_id') !!}" id="student_id" name="student_id" value="{{ $old['student_id'] ?? '' }}" maxlength="7" placeholder="112233" required>
            <div class="form-error" id="error_student_id"><?php if ($errorFor('student_id')): ?>{{ $errorFor('student_id') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="age">Age <span class="req">*</span></label>
            <input class="input{!! $invalid('age') !!}" id="age" name="age" type="number" min="10" max="100" value="{{ $old['age'] ?? '' }}" required>
            <div class="form-error" id="error_age"><?php if ($errorFor('age')): ?>{{ $errorFor('age') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="email">Email address <span class="req">*</span></label>
            <input class="input{!! $invalid('email') !!}" id="email" name="email" type="email" value="{{ $old['email'] ?? '' }}" maxlength="190" placeholder="name@uc.edu.ph" autocomplete="email" required>
            <div class="form-error" id="error_email"><?php if ($errorFor('email')): ?>{{ $errorFor('email') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="program">Program / Course <span class="req">*</span></label>
            <select class="input{!! $invalid('program') !!}" id="program" name="program" required>
                <option value="">Select your program</option>
                <?php foreach ($programs as $program): ?>
                    <option value="{{ $program }}"{!! $selected($program, $old['program'] ?? '') !!}>{{ $program }}</option>
                <?php endforeach; ?>
            </select>
            <div class="form-error" id="error_program"><?php if ($errorFor('program')): ?>{{ $errorFor('program') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="phone">Mobile number <span class="req">*</span></label>
            <input class="input{!! $invalid('phone') !!}" id="phone" name="phone" value="{{ $old['phone'] ?? '' }}" maxlength="11" placeholder="09XXXXXXXXX" required>
            <div class="form-error" id="error_phone"><?php if ($errorFor('phone')): ?>{{ $errorFor('phone') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="year_level">Year level <span class="req">*</span></label>
            <select class="input{!! $invalid('year_level') !!}" id="year_level" name="year_level" required>
                <option value="">Select your year level</option>
                <?php foreach ($yearLevels as $year): ?>
                    <option value="{{ $year }}"{!! $selected($year, $old['year_level'] ?? '') !!}>{{ $year }}</option>
                <?php endforeach; ?>
            </select>
            <div class="form-error" id="error_year_level"><?php if ($errorFor('year_level')): ?>{{ $errorFor('year_level') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="password">Password <span class="req">*</span></label>
            <input class="input{!! $invalid('password') !!}" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
            <div class="help-text">At least 12 characters.</div>
            <div class="form-error" id="error_password"><?php if ($errorFor('password')): ?>{{ $errorFor('password') }}<?php endif; ?></div>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm password <span class="req">*</span></label>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
            <div class="form-error" id="error_password_confirmation"></div>
        </div>

        <div class="field">
            <label for="school_id">School ID verification <span class="req">*</span></label>
            <input class="input{!! $invalid('school_id') !!}" id="school_id" name="school_id" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required>
            <div class="help-text">JPG, PNG or PDF, up to 5 MB. Stored securely and reviewed by OSAS.</div>
            <div class="form-error" id="error_school_id"><?php if ($errorFor('school_id')): ?>{{ $errorFor('school_id') }}<?php endif; ?></div>
        </div>
    </div>

    <button type="submit" id="submit-btn" class="btn btn--primary btn--block auth__submit">Submit for Verification</button>
</form>

<div class="auth__alt">
    <div class="auth__alt-row">
        <span class="auth__alt-line"></span><span>Already have an account?</span><span class="auth__alt-line"></span>
    </div>
    <a href="{!! url('/login') !!}" class="btn btn--outline btn--block">Back to Sign In</a>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("register-form");
    if (!form) return;

    var firstName = document.getElementById("first_name");
    var middleName = document.getElementById("middle_name");
    var lastName = document.getElementById("last_name");
    var studentId = document.getElementById("student_id");
    var age = document.getElementById("age");
    var phone = document.getElementById("phone");

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

    // First Name, Surname: letters only
    var letterRegex = /^[a-zA-ZñÑ\s\-\.]$/;
    restrictInput(firstName, letterRegex);
    restrictInput(lastName, letterRegex);

    // Middle Name: letters only (optional, can be empty)
    restrictInput(middleName, letterRegex);

    // Student ID: numbers only
    var digitRegex = /^\d$/;
    restrictInput(studentId, digitRegex);

    // Age: numbers only
    restrictInput(age, digitRegex);

    // Phone: numbers only
    restrictInput(phone, digitRegex);

    // AJAX Form submission to prevent full page reload (preserving previously inputted info)
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        var submitBtn = document.getElementById("submit-btn");
        var alertContainer = document.getElementById("register-alert-container");
        
        alertContainer.innerHTML = "";
        var inputs = form.querySelectorAll(".input");
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].classList.remove("input--error");
        }
        var errors = form.querySelectorAll(".form-error");
        for (var j = 0; j < errors.length; j++) {
            errors[j].textContent = "";
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Submitting...";

        var formData = new FormData(form);

        fetch(form.action, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            },
            body: formData
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return { status: response.status, data: data };
            });
        })
        .then(function (res) {
            submitBtn.disabled = false;
            submitBtn.textContent = "Submit for Verification";

            if (res.status === 200 && res.data.success) {
                window.location.href = res.data.redirect || "{{ url('/login') }}";
            } else if (res.status === 422 && res.data.errors) {
                var firstErrorMsg = "";
                for (var field in res.data.errors) {
                    if (res.data.errors.hasOwnProperty(field)) {
                        var msg = res.data.errors[field][0];
                        if (!firstErrorMsg) firstErrorMsg = msg;
                        var inputEl = document.getElementById(field);
                        var errEl = document.getElementById("error_" + field);
                        if (inputEl) inputEl.classList.add("input--error");
                        if (errEl) errEl.textContent = msg;
                    }
                }

                alertContainer.innerHTML = 
                    '<div class="alert alert--danger" role="alert">' +
                    '<span class="alert__ico">{!! App\Support\Icons::render("alert", 18) !!}</span>' +
                    '<div>' +
                    '<div class="alert__title">Please correct the highlighted fields</div>' +
                    '<div class="alert__text">' + (firstErrorMsg || res.data.message) + '</div>' +
                    '</div>' +
                    '</div>';

                window.scrollTo({ top: 0, behavior: "smooth" });
            } else {
                alertContainer.innerHTML = 
                    '<div class="alert alert--danger" role="alert">' +
                    '<span class="alert__ico">{!! App\Support\Icons::render("alert", 18) !!}</span>' +
                    '<div>' +
                    '<div class="alert__title">Error</div>' +
                    '<div class="alert__text">' + (res.data.message || "An unexpected error occurred.") + '</div>' +
                    '</div>' +
                    '</div>';
            }
        })
        .catch(function (err) {
            submitBtn.disabled = false;
            submitBtn.textContent = "Submit for Verification";
            alertContainer.innerHTML = 
                '<div class="alert alert--danger" role="alert">' +
                '<span class="alert__ico">{!! App\Support\Icons::render("alert", 18) !!}</span>' +
                '<div>' +
                '<div class="alert__title">Error</div>' +
                '<div class="alert__text">Network or server error. Please try again.</div>' +
                '</div>' +
                '</div>';
        });
    });
});
</script>

@endsection
