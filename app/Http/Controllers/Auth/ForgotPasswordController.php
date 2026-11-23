<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Mailer;
use Illuminate\Http\Request;

final class ForgotPasswordController extends Controller
{
    public function show(Request $request)
    {
        return view('auth/forgot-password', [
            'title' => 'Reset Portal Password',
            'pageTitle' => 'Reset Portal Password',
            'authLogoRight' => true,
            'errors' => session('errors', []),
            'status' => session('status'),
            'old' => ['email' => old('email', '')],
        ]);
    }

    public function store(Request $request)
    {
        $data = (new ForgotPasswordRequest())->validate($request);
        $user = User::findByEmail($data['email']);

        if ($user !== null) {
            $code = PasswordReset::createForUser($user);

            $sent = Mailer::send(
                (string) $user['email'],
                'Your BEACON OSAS Password Reset Code',
                "Hello " . User::fullName($user) . ",\n\n"
                . "Your 6-digit password reset code is: " . $code . "\n\n"
                . "This code expires in 15 minutes.\n\n"
                . "If you did not request this, you can ignore this message.\n",
            );

            if (!$sent) {
                PasswordReset::deleteForEmail((string) $user['email']);
            } else {
                AuditLogger::log(
                    'auth.password_reset_requested',
                    'Password Reset Requested',
                    'A password reset code was requested for ' . $user['email'] . '.',
                    [
                        'target_user_id' => (int) $user['id'],
                        'entity_type' => 'user',
                        'entity_id' => (string) $user['public_id'],
                        'tone' => 'warning',
                        'icon' => 'mail',
                        'ip_address' => $request->ip(),
                    ],
                );
            }
        }

        session()->flash('status', 'If that email belongs to a portal account, a 6-digit reset code has been sent. It expires in 15 minutes.');
        session()->flash('reset_email', $data['email']);

        return redirect(url('/reset-password/verify'));
    }
}
