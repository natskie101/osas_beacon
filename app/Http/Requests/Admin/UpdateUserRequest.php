<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\FormRequest;

final class UpdateUserRequest extends FormRequest
{
    public function __construct(private readonly int $ignoreId)
    {
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:80',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:80',
            'email' => 'required|email|max:190|unique:users,email,' . $this->ignoreId,
            'username' => 'nullable|string|max:100|unique:users,username,' . $this->ignoreId,
            'role' => 'required|in:Coordinator,Administrator,Student',
            'student_id' => 'nullable|string|max:30',
            'age' => 'nullable|integer|min:10|max:100',
            'department' => 'nullable|string|max:150',
            'program' => 'nullable|string|max:150',
            'year_level' => 'nullable|string|max:30',
            'access_level' => 'nullable|string|max:150',
            'status' => 'required|in:Active,Pending,Inactive',
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8|max:255|confirmed',
        ];
    }
}
