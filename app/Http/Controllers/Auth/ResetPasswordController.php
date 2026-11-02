<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class ResetPasswordController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $reset = PasswordReset::findValid($token);

        if ($reset === null) {
            Session::flash('error', 'That reset link is invalid or has expired. Request a new link below.');

            return Response::redirect(url('/forgot-password'));
        }

        return $this->page('layouts.auth', 'auth/reset-password', [
            'title' => 'Choose a New Password',
            'pageTitle' => 'Choose a New Password',
            'token' => $token,
            'email' => (string) $reset['email'],
            'errors' => Session::errors(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = (new ResetPasswordRequest())->validate($request);
        $reset = PasswordReset::findValid($data['token']);

        if ($reset === null || mb_strtolower((string) $reset['email']) !== mb_strtolower($data['email'])) {
            Session::flash('error', 'That reset link is invalid or has expired. Request a new link below.');

            return Response::redirect(url('/forgot-password'));
        }

        User::updatePassword((int) $reset['user_id'], $data['password']);
        PasswordReset::markUsed((int) $reset['id']);
        PasswordReset::deleteForEmail((string) $reset['email']);

        AuditLogger::log(
            'auth.password_reset',
            'Password Changed',
            'The portal password was reset for ' . $reset['email'] . '.',
            [
                'target_user_id' => (int) $reset['user_id'],
                'entity_type' => 'user',
                'entity_id' => '',
                'tone' => 'warning',
                'icon' => 'lock',
                'ip_address' => $request->ip,
            ],
        );

        Session::flash('status', 'Your password has been updated. Sign in with your new password.');

        return Response::redirect(url('/login'));
    }
}
