@extends('layouts.auth')
@section('content')
<?php
/** @var array<string, string> $errors */
/** @var string|null $status */
/** @var array<string, mixed> $old */
$errorMessage = !empty($errors) ? (string) reset($errors) : null;
$notices = [];
if (!empty($status)) {
    $notices[] = ['text' => $status, 'error' => false];
}
if ($errorMessage !== null) {
    $notices[] = ['text' => $errorMessage, 'error' => true];
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
    <h1 class="auth__title">Enter Reset Code</h1>
    <p class="auth__subtitle">Enter the 6-digit verification code sent to your email.</p>
</div>

<form class="auth__form-inner" method="POST" action="{!! url('/reset-password/verify') !!}" novalidate>
    {!! csrf_field() !!}
    <div class="field">
        <label for="email">University Email</label>
        <span class="input-group">
            <span class="input-group__ico" aria-hidden="true">{!! App\Support\Icons::render('mail', 16) !!}</span>
            <input class="input" id="email" name="email" type="email" value="{{ $old['email'] ?? session('reset_email', '') }}" placeholder="name@uc.edu.ph" autocomplete="email" required>
        </span>
    </div>

    <div class="field">
        <label for="code">6-Digit Code</label>
        <span class="input-group">
            <span class="input-group__ico" aria-hidden="true">{!! App\Support\Icons::render('lock', 16) !!}</span>
            <input class="input" id="code" name="code" type="text" inputmode="numeric" maxlength="6" placeholder="123456" required>
        </span>
    </div>

    <button type="submit" class="btn btn--primary btn--block">Verify Code</button>
</form>

<div class="auth__alt">
    <a href="{!! url('/forgot-password') !!}" class="btn btn--outline btn--block">Request New Code</a>
</div>

@endsection
