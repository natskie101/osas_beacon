<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Services\UserService;
use App\Support\Exceptions\ValidationException;
use Illuminate\Http\Request;
use RuntimeException;

final class RegisterController extends Controller
{
    public function show(Request $request)
    {
        return view('auth/register', [
            'title' => 'Create Student Account',
            'pageTitle' => 'Create Student Account',
            'authSingle' => true,
            'errors' => session('errors', []),
            'old' => [
                'first_name' => old('first_name', ''),
                'middle_name' => old('middle_name', ''),
                'last_name' => old('last_name', ''),
                'student_id' => old('student_id', ''),
                'age' => old('age', ''),
                'email' => old('email', ''),
                'program' => old('program', ''),
                'phone' => old('phone', ''),
                'year_level' => old('year_level', ''),
            ],
            'programs' => config('people.programs', []),
            'yearLevels' => config('people.year_levels', []),
        ]);
    }

    public function store(Request $request)
    {
        $data = (new RegisterStudentRequest())->validate($request);
        $file = $this->uploadedFile($request, 'school_id');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(
                ['school_id' => 'Upload a clear photo or PDF of your school ID.'],
                $request->input(),
            );
        }

        try {
            UserService::registerStudent($data, $file);
        } catch (RuntimeException $exception) {
            throw new ValidationException(['school_id' => $exception->getMessage()], $request->input());
        }

        session()->flash('registration_success', 'Your account was submitted for verification. OSAS will review your school ID before you can sign in.');

        return redirect(url('/login'));
    }
}
