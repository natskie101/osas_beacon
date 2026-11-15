<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'token' => 'required|string|max:128',
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:8|max:255|confirmed',
        ];
    }
}
