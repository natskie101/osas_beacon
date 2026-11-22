<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

final class ResetPasswordController extends Controller
{
    public function showVerify(Request $request)
    {
        return view('auth/verify-reset-code', [
            'title' => 'Enter Reset Code',
            'pageTitle' => 'Enter Reset Code',
            'errors' => session('errors', []),
            'status' => session('status'),
            'old' => ['email' => old('email', session('reset_email', ''))],
        ]);
    }

    public function storeVerify(Request $request)
    {
        $email = trim((string) $request->input('email', ''));
        $code = trim((string) $request->input('code', ''));

        if ($email === '' || $code === '') {
            session()->flash('errors', ['code' => 'Please provide both your email and the 6-digit code.']);
            session()->flashInput(['email' => $email]);

            return redirect(url('/reset-password/verify'));
        }

        $reset = PasswordReset::findValidByCode($email, $code);

        if ($reset === null) {
            session()->flash('errors', ['code' => 'That reset code is invalid or has expired. Request a new code below.']);
            session()->flashInput(['email' => $email]);

            return redirect(url('/reset-password/verify'));
        }

        // Mark code as verified in session so they can proceed to new password page
        session([
            'password_reset_verified' => true,
            'password_reset_email' => (string) $reset['email'],
            'password_reset_id' => (int) $reset['id'],
            'password_reset_user_id' => (int) $reset['user_id'],
        ]);

        return redirect(url('/reset-password/new'));
    }

    public function showNew(Request $request)
    {
        if (!session('password_reset_verified')) {
            session()->flash('error', 'Please verify your reset code first.');

            return redirect(url('/forgot-password'));
        }

        return view('auth/reset-password', [
            'title' => 'Choose a New Password',
            'pageTitle' => 'Choose a New Password',
            'email' => (string) session('password_reset_email', ''),
            'errors' => session('errors', []),
        ]);
    }

    public function storeNew(Request $request)
    {
        if (!session('password_reset_verified')) {
            session()->flash('error', 'Please verify your reset code first.');

            return redirect(url('/forgot-password'));
        }

        $data = (new ResetPasswordRequest())->validate($request);
        $email = (string) session('password_reset_email', '');
        $resetId = (int) session('password_reset_id', 0);
        $userId = (int) session('password_reset_user_id', 0);

        if ($email === '' || $userId === 0 || mb_strtolower($email) !== mb_strtolower($data['email'])) {
            session()->flash('error', 'Session expired or email mismatch. Please start over.');

            return redirect(url('/forgot-password'));
        }

        User::updatePassword($userId, $data['password']);
        if ($resetId > 0) {
            PasswordReset::markUsed($resetId);
        }
        PasswordReset::deleteForEmail($email);

        // Clear verification session data
        session()->forget(['password_reset_verified', 'password_reset_email', 'password_reset_id', 'password_reset_user_id']);

        AuditLogger::log(
            'auth.password_reset',
            'Password Changed',
            'The portal password was reset for ' . $email . '.',
            [
                'target_user_id' => $userId,
                'entity_type' => 'user',
                'entity_id' => '',
                'tone' => 'warning',
                'icon' => 'lock',
                'ip_address' => $request->ip(),
            ],
        );

        session()->flash('status', 'Your password has been updated. Sign in with your new password.');

        return redirect(url('/login'));
    }
}
