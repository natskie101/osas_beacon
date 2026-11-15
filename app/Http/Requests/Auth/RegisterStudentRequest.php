<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

final class RegisterStudentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-ZñÑ\s\-\.]+$/'],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZñÑ\s\-\.]+$/'],
            'last_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-ZñÑ\s\-\.]+$/'],
            'student_id' => ['required', 'string', 'max:7', 'regex:/^\d+$/', 'unique:users,student_id'],
            'age' => ['required', 'integer', 'min:10', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'program' => ['required', 'in:' . implode(',', config('people.programs', []))],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[\+\d\s\-\(\)]+$/'],
            'year_level' => ['required', 'in:' . implode(',', config('people.year_levels', []))],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
        ];
    }
}
