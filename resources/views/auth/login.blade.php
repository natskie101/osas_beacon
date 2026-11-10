@extends('layouts.auth')
@section('content')
<?php
/** @var array<string, string> $errors */
/** @var string|null $error */
/** @var string|null $status */
/** @var array<string, mixed> $old */
/** @var bool $demoMode */
/** @var string|null $registrationSuccess */
$message = $error ?: (reset($errors) ?: null);
$notices = [];
if (!empty($status)) {
    $notices[] = ['text' => $status, 'error' => false];
}
if ($message !== null) {
    $notices[] = ['text' => $message, 'error' => true];
}
?>
<?php foreach ($notices as $notice): ?>
    <div class="auth-signout-toast{!! $notice['error'] ? ' auth-signout-toast--error' : '' !!}"
         role="{!! $notice['error'] ? 'alert' : 'status' !!}"
         aria-live="{!! $notice['error'] ? 'assertive' : 'polite' !!}"
         data-auto-dismiss-toast>
        <span class="auth-signout-toast__icon">{!! App\Support\Icons::render($notice['error'] ? 'alert' : 'checkCircle', 18) !!}</span>
        <span>{{ $notice['text'] }}</span>
        <button type="button" class="auth-signout-toast__close" data-dismiss-toast aria-label="Dismiss message">&times;</button>
    </div>
<?php endforeach; ?>

<div class="auth__header">
    <h1 class="auth__title">Sign in to Portal</h1>
    <p class="auth__subtitle">Please enter your authorized credentials to proceed</p>
</div>

<form class="auth__form-inner" method="POST" action="{!! url('/login') !!}" novalidate>
    {!! csrf_field() !!}

    <div class="field">
        <label for="email">User Email</label>
        <span class="input-group">
            <span class="input-group__ico" aria-hidden="true">{!! App\Support\Icons::render('user', 16) !!}</span>
            <input class="input"
                   id="email"
                   name="email"
                   type="email"
                   value="{{ $old['email'] ?? '' }}"
                   placeholder="Enter your email"
                   autocomplete="username"
                   required>
        </span>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <span class="input-group">
            <span class="input-group__ico" aria-hidden="true">{!! App\Support\Icons::render('lock', 16) !!}</span>
            <input class="input"
                   id="password"
                   name="password"
                   type="password"
                   placeholder="Enter your password"
                   autocomplete="current-password"
                   required>
            <button type="button" class="input-group__toggle" data-password-toggle="password" aria-label="Show password">
                {!! App\Support\Icons::render('eye', 16) !!}
            </button>
        </span>
    </div>

    <div class="field field--inline">
        <label class="checkbox">
            <input type="checkbox" name="remember" value="1">
            <span class="checkbox__box"></span>
            <span>Remember this device</span>
        </label>
        <a href="{!! url('/forgot-password') !!}" class="link">Forgot Password?</a>
    </div>

    <button type="submit" class="btn btn--primary btn--block auth__submit">
        Login
    </button>
</form>



<div class="auth__alt">
    <div class="auth__alt-row">
        <span class="auth__alt-line"></span><span>New to the portal?</span><span class="auth__alt-line"></span>
    </div>
    <a href="{!! url('/create-account') !!}" class="btn btn--outline btn--block">Create Student Account</a>

</div>

<?php if (!empty($registrationSuccess)): ?>
    <div class="auth-pop" role="alertdialog" aria-modal="true" aria-labelledby="registration-pop-title" aria-describedby="registration-pop-text">
        <div class="auth-pop__card">
            <span class="auth-pop__icon">{!! App\Support\Icons::render('checkCircle', 30) !!}</span>
            <h3 class="auth-pop__title" id="registration-pop-title">Account submitted for verification</h3>
            <p class="auth-pop__text" id="registration-pop-text">{{ $registrationSuccess }}</p>
            <a href="{!! url('/login') !!}" class="btn btn--primary btn--block">Continue to Sign In</a>
        </div>
    </div>
<?php endif; ?>

@endsection
