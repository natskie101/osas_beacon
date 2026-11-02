<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Services\UserService;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use RuntimeException;

final class RegisterController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->page('layouts.auth', 'auth/register', [
            'title' => 'Create Student Account',
            'pageTitle' => 'Create Student Account',
            'authSingle' => true,
            'errors' => Session::errors(),
            'old' => [
                'first_name' => Session::old('first_name', ''),
                'middle_name' => Session::old('middle_name', ''),
                'last_name' => Session::old('last_name', ''),
                'student_id' => Session::old('student_id', ''),
                'age' => Session::old('age', ''),
                'email' => Session::old('email', ''),
                'program' => Session::old('program', ''),
                'phone' => Session::old('phone', ''),
                'year_level' => Session::old('year_level', ''),
            ],
            'programs' => config('people.programs', []),
            'yearLevels' => config('people.year_levels', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = (new RegisterStudentRequest())->validate($request);
        $file = $request->file('school_id');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(
                ['school_id' => 'Upload a clear photo or PDF of your school ID.'],
                $request->all(),
            );
        }

        try {
            UserService::registerStudent($data, $file);
        } catch (RuntimeException $exception) {
            throw new ValidationException(['school_id' => $exception->getMessage()], $request->all());
        }

        Session::flash('registration_success', 'Your account was submitted for verification. OSAS will review your school ID before you can sign in.');

        return Response::redirect(url('/login'));
    }
}
