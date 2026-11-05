<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Mailer;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class ForgotPasswordController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->page('layouts.auth', 'auth/forgot-password', [
            'title' => 'Reset Portal Password',
            'pageTitle' => 'Reset Portal Password',
            'authLogoRight' => true,
            'errors' => Session::errors(),
            'status' => Session::getFlash('status'),
            'old' => ['email' => Session::old('email', '')],
        ]);
    }

    public function store(Request $request): Response
    {
        $data = (new ForgotPasswordRequest())->validate($request);
        $user = User::findByEmail($data['email']);

        if ($user !== null) {
            $token = PasswordReset::createForUser($user);
            $resetUrl = absolute_url('/reset-password/' . $token);

            Mailer::send(
                (string) $user['email'],
                'Reset your BEACON OSAS password',
                "Hello " . User::fullName($user) . ",\n\n"
                . "Use the link below to choose a new password. It expires in 15 minutes.\n\n"
                . $resetUrl . "\n\n"
                . "If you did not request this, you can ignore this message.\n",
            );

            AuditLogger::log(
                'auth.password_reset_requested',
                'Password Reset Requested',
                'A password reset link was requested for ' . $user['email'] . '.',
                [
                    'target_user_id' => (int) $user['id'],
                    'entity_type' => 'user',
                    'entity_id' => (string) $user['public_id'],
                    'tone' => 'warning',
                    'icon' => 'mail',
                    'ip_address' => $request->ip,
                ],
            );
        }

        Session::flash('status', 'If that email belongs to a portal account, reset instructions are being processed. Any reset link expires in 15 minutes.');

        return Response::redirect(url('/forgot-password'));
    }
}
