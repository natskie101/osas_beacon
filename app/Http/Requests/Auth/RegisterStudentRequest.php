<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

final class RegisterStudentRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:80',
            'middle_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:80',
            'student_id' => 'required|string|max:30|unique:users,student_id',
            'age' => 'required|integer|min:10|max:100',
            'email' => 'required|email|max:190|unique:users,email',
            'program' => 'required|in:' . implode(',', config('people.programs', [])),
            'phone' => 'required|string|max:30',
            'year_level' => 'required|in:' . implode(',', config('people.year_levels', [])),
            'password' => 'required|string|min:8|max:255|confirmed',
        ];
    }
}
