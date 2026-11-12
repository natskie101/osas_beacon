@extends('layouts.auth')
@section('content')
<?php
/** @var array<string, string> $errors */
/** @var string $token */
/** @var string $email */
?>
<div class="auth__header">
    <h1 class="auth__title">Choose a New Password</h1>
    <p class="auth__subtitle">Set a password of at least 8 characters for {{ $email }}.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span>
        <div><div class="alert__text">{{ reset($errors) }}</div></div>
    </div>
<?php endif; ?>

<form class="auth__form-inner" method="POST" action="{!! url('/reset-password') !!}" novalidate>
    {!! csrf_field() !!}
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="field">
        <label for="email">University Email</label>
        <span class="input-group">
            <span class="input-group__ico" aria-hidden="true">{!! App\Support\Icons::render('mail', 16) !!}</span>
            <input class="input" id="email" name="email" type="email" value="{{ $email }}" readonly>
        </span>
    </div>

    <div class="field">
        <label for="password">New Password</label>
        <span class="input-group">
            <span class="input-group__ico" aria-hidden="true">{!! App\Support\Icons::render('lock', 16) !!}</span>
            <input class="input" id="password" name="password" type="password" placeholder="At least 8 characters" autocomplete="new-password" required>
            <button type="button" class="input-group__toggle" data-password-toggle="password" aria-label="Show password">
                {!! App\Support\Icons::render('eye', 16) !!}
            </button>
        </span>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm New Password</label>
        <span class="input-group">
            <span class="input-group__ico" aria-hidden="true">{!! App\Support\Icons::render('lock', 16) !!}</span>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Re-enter the new password" autocomplete="new-password" required>
        </span>
    </div>

    <button type="submit" class="btn btn--primary btn--block">Update Password</button>
</form>

<div class="auth__alt">
    <a href="{!! url('/login') !!}" class="btn btn--outline btn--block">Back to Sign In</a>
</div>

@endsection
