<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

final class LoginRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:190',
            'password' => 'required|string|max:255',
        ];
    }
}
