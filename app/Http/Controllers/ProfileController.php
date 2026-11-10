<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\RememberToken;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Support\Auth;
use App\Support\DB;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use Illuminate\Http\Request;
use App\Support\Session;
use RuntimeException;
use Throwable;

final class ProfileController extends Controller
{
    public function show(Request $_request)
    {
        $user = Auth::user();
        $old = Session::getFlash('old', []);
        $values = array_merge($user, \is_array($old) ? $old : []);
        $values['password'] = '';
        $values['password_confirmation'] = '';
        $values['current_password'] = '';

        return view('account/profile', [
            'title' => 'My Profile',
            'pageTitle' => 'My Profile',
            'pageSubtitle' => 'Manage your personal and account information.',
            'pageKey' => 'profile',
            'user' => $user,
            'role' => $user['role'],
            'values' => $values,
            'profilePhotoFile' => File::latestForEntity('user_profile_photo', (string) $user['id']),
            'errors' => Session::errors(),
            'departments' => config('people.departments', []),
            'programs' => config('people.programs', []),
            'yearLevels' => config('people.year_levels', []),
        ]);
    }

    public function photo(Request $_request)
    {
        $user = Auth::user();
        $file = File::latestForEntity('user_profile_photo', (string) $user['id']);

        if ($file === null || !in_array($file['mime_type'] ?? '', ['image/jpeg', 'image/png'], true)) {
            throw new NotFoundHttpException('The profile photo could not be found.');
        }

        $path = FileStorage::path($file);
        $content = is_file($path) ? file_get_contents($path) : false;

        if ($content === false) {
            throw new NotFoundHttpException('The profile photo could not be found.');
        }

        return response($content, 200, [
            'Content-Type' => (string) $file['mime_type'],
            'Content-Length' => (string) strlen($content),
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $userId = (int) $user['id'];
        $rules = [
            'first_name' => 'required|string|max:80',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:80',
            'email' => 'required|email|max:190|unique:users,email,' . $userId,
            'username' => 'required|string|max:100|unique:users,username,' . $userId,
            'phone' => 'nullable|string|max:30',
            'current_password' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|max:255|confirmed',
        ];

        if ($user['role'] === 'Student') {
            $rules += [
                'age' => 'required|integer|min:10|max:100',
                'department' => 'nullable|in:' . implode(',', config('people.departments', [])),
                'program' => 'required|in:' . implode(',', config('people.programs', [])),
                'year_level' => 'required|in:' . implode(',', config('people.year_levels', [])),
            ];
        } else {
            $rules['department'] = 'nullable|in:' . implode(',', config('people.departments', []));
        }

        $data = $this->validate($request, $rules);
        $emailChanged = mb_strtolower(trim((string) $data['email'])) !== mb_strtolower((string) $user['email']);
        $passwordChanged = (string) ($data['password'] ?? '') !== '';

        if (($emailChanged || $passwordChanged)
            && !password_verify((string) ($data['current_password'] ?? ''), (string) $user['password_hash'])) {
            throw new ValidationException(
                ['current_password' => 'Enter your current password to change your email or password.'],
                $request->input(),
            );
        }

        $updates = [
            'first_name' => trim((string) $data['first_name']),
            'middle_name' => trim((string) ($data['middle_name'] ?? '')) ?: null,
            'last_name' => trim((string) $data['last_name']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'username' => trim((string) $data['username']),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'department' => trim((string) ($data['department'] ?? '')) ?: null,
        ];

        if ($user['role'] === 'Student') {
            $updates += [
                'age' => (int) $data['age'],
                'program' => trim((string) $data['program']),
                'year_level' => trim((string) $data['year_level']),
            ];
        }

        $existingPhoto = File::latestForEntity('user_profile_photo', (string) $userId);
        $newPhoto = $this->storeProfilePhoto($request, $userId);
        $removePhoto = $request->boolean('remove_profile_photo');

        try {
            DB::transaction(static function () use ($user, $userId, $updates, $emailChanged, $passwordChanged, $data): void {
                User::update($userId, $updates);

                if ($emailChanged) {
                    DB::update(
                        'UPDATE lost_found_items SET reporter_email = ? WHERE reporter_email = ?',
                        [$updates['email'], (string) $user['email']],
                    );
                }

                if ($passwordChanged) {
                    User::updatePassword($userId, (string) $data['password']);
                    RememberToken::clearForUser($userId);
                }

                AuditLogger::log(
                    'user.profile_updated',
                    'Account Profile Updated',
                    'The account owner updated their profile information.',
                    [
                        'target_user_id' => $userId,
                        'entity_type' => 'user',
                        'entity_id' => (string) $user['public_id'],
                        'tone' => 'blue',
                        'icon' => 'edit',
                    ],
                );
            });
        } catch (Throwable $exception) {
            if ($newPhoto !== null) {
                FileStorage::delete($newPhoto);
            }

            throw $exception;
        }

        if ($existingPhoto !== null && ($newPhoto !== null || $removePhoto)) {
            FileStorage::delete($existingPhoto);
        }

        $updated = User::find($userId);

        if ($updated === null) {
            throw new RuntimeException('The account could not be loaded after updating the profile.');
        }

        Auth::refresh($updated);
        Session::flash('status', 'Your profile has been updated.');

        return redirect(url('/profile'));
    }

    /** @return array<string, mixed>|null */
    private function storeProfilePhoto(Request $request, int $userId): ?array
    {
        $file = $this->uploadedFile($request, 'profile_photo');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        try {
            return FileStorage::storeUpload(
                $file,
                'profile-photos/' . $userId,
                'user_profile_photo',
                (string) $userId,
                $userId,
                5 * 1024 * 1024,
                ['image/jpeg' => 'jpg', 'image/png' => 'png'],
            );
        } catch (RuntimeException $exception) {
            throw new ValidationException(['profile_photo' => $exception->getMessage()], $request->input());
        }
    }
}
