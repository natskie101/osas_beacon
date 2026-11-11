<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use App\Models\PasswordReset;
use App\Models\RememberToken;
use App\Models\User;
use App\Support\Exceptions\ForbiddenHttpException;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Support\Facades\DB;

final class UserService
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $schoolIdFile
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public static function create(array $data, ?array $schoolIdFile, array $actor): array
    {
        return DB::transaction(function () use ($data, $schoolIdFile, $actor): array {
            $data = self::normalize($data);
            $id = User::create($data);
            $target = User::find($id);

            if ($target === null) {
                throw new \RuntimeException('The account could not be loaded after creation.');
            }

            if ($schoolIdFile !== null && (int) ($schoolIdFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                self::attachSchoolId($target, $schoolIdFile, $actor);
                $target = User::find($id) ?? $target;
            }

            AuditLogger::log(
                'user.created',
                'Account Created',
                User::fullName($target) . ' (' . $target['public_id'] . ') was created with ' . $target['role'] . ' access.',
                [
                    'target_user_id' => $id,
                    'entity_type' => 'user',
                    'entity_id' => (string) $target['public_id'],
                    'tone' => 'green',
                    'icon' => 'users',
                    'actor_user_id' => $actor['id'],
                    'actor_name' => User::fullName($actor),
                ],
            );

            NotificationService::send(
                $id,
                'Welcome to the BEACON Portal',
                'Your portal account has been created. You can sign in with your university email address.',
                'success',
                'user',
                (string) $target['public_id'],
            );

            return $target;
        });
    }

    /**
     * Public student self-registration. The account starts as Pending until a
     * coordinator verifies the uploaded school ID.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $schoolIdFile
     * @return array<string, mixed>
     */
    public static function registerStudent(array $data, array $schoolIdFile): array
    {
        return DB::transaction(function () use ($data, $schoolIdFile): array {
            $data['role'] = 'Student';
            $data['status'] = 'Pending';
            $data = self::normalize($data);
            $data['requested_at'] = date('Y-m-d H:i:s');

            $id = User::create($data);
            $target = User::find($id);

            if ($target === null) {
                throw new \RuntimeException('The account could not be loaded after creation.');
            }

            $stored = FileStorage::storeUpload(
                $schoolIdFile,
                'school-ids/' . $id,
                'user_school_id',
                (string) $id,
                $id,
            );

            User::update($id, ['school_id_file_id' => (int) $stored['id']]);
            $target = User::find($id) ?? $target;

            AuditLogger::log(
                'user.registration_submitted',
                'Student Account Registration',
                User::fullName($target) . ' (' . $target['public_id'] . ') submitted a student account for school ID verification.',
                [
                    'target_user_id' => $id,
                    'entity_type' => 'user',
                    'entity_id' => (string) $target['public_id'],
                    'tone' => 'amber',
                    'icon' => 'user',
                    'actor_name' => 'Self-registration',
                ],
            );

            return $target;
        });
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $schoolIdFile
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public static function update(array $target, array $data, ?array $schoolIdFile, array $actor): array
    {
        return DB::transaction(function () use ($target, $data, $schoolIdFile, $actor): array {
            $roleChanged = (string) $data['role'] !== (string) $target['role'];
            $statusChanged = (string) $data['status'] !== (string) $target['status'];
            $data = self::normalize($data);

            if ($roleChanged) {
                $data['access_level'] = (string) config('people.access_levels.' . $data['role'], '');
            }

            $fields = [
                'first_name', 'middle_name', 'last_name', 'email', 'username', 'role', 'student_id', 'age',
                'department', 'program', 'year_level', 'access_level', 'account_tier', 'status', 'phone',
            ];

            $update = [];

            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $update[$field] = $data[$field];
                }
            }

            if (trim((string) ($update['username'] ?? '')) === '') {
                unset($update['username']);
            }

            if (($update['status'] ?? '') === 'Active' && empty($target['email_verified_at'])) {
                $update['email_verified_at'] = date('Y-m-d H:i:s');
            }

            User::update((int) $target['id'], $update);

            if (!empty($data['password'])) {
                User::updatePassword((int) $target['id'], (string) $data['password']);
            }

            if ($schoolIdFile !== null && (int) ($schoolIdFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                self::attachSchoolId($target, $schoolIdFile, $actor);
            }

            if ($statusChanged && (string) $data['status'] !== 'Active') {
                RememberToken::clearForUser((int) $target['id']);
            }

            $updated = User::find((int) $target['id']) ?? $target;

            AuditLogger::log(
                'user.updated',
                $roleChanged ? 'Account Role Updated' : 'Account Updated',
                User::fullName($updated) . ' (' . $updated['public_id'] . ') had their account details updated.',
                [
                    'target_user_id' => (int) $updated['id'],
                    'entity_type' => 'user',
                    'entity_id' => (string) $updated['public_id'],
                    'tone' => 'blue',
                    'icon' => 'edit',
                    'actor_user_id' => $actor['id'],
                    'actor_name' => User::fullName($actor),
                ],
            );

            NotificationService::send(
                (int) $updated['id'],
                'Your Portal Account Was Updated',
                'An OSAS staff member updated your account details. If you did not expect this, contact OSAS Support.',
                'info',
                'user',
                (string) $updated['public_id'],
            );

            if (!empty($data['password'])) {
                AuditLogger::log(
                    'user.password_changed',
                    'Password Changed by Staff',
                    'A staff member changed the portal password for ' . $updated['email'] . '.',
                    [
                        'target_user_id' => (int) $updated['id'],
                        'entity_type' => 'user',
                        'entity_id' => (string) $updated['public_id'],
                        'tone' => 'amber',
                        'icon' => 'lock',
                        'actor_user_id' => $actor['id'],
                        'actor_name' => User::fullName($actor),
                    ],
                );
            }

            return $updated;
        });
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $actor
     */
    public static function setStatus(
        array $target,
        string $status,
        array $actor,
        string $action,
        string $title,
        string $message,
        string $tone = 'brand',
    ): void {
        DB::transaction(function () use ($target, $status, $actor, $action, $title, $message, $tone): void {
            $fields = ['status' => $status];

            if ($status === 'Active' && empty($target['email_verified_at'])) {
                $fields['email_verified_at'] = date('Y-m-d H:i:s');
            }

            User::update((int) $target['id'], $fields);

            if ($status !== 'Active') {
                RememberToken::clearForUser((int) $target['id']);
            }

            AuditLogger::log(
                $action,
                $title,
                User::fullName($target) . ' (' . $target['public_id'] . ') — ' . $message,
                [
                    'target_user_id' => (int) $target['id'],
                    'entity_type' => 'user',
                    'entity_id' => (string) $target['public_id'],
                    'tone' => $tone,
                    'icon' => $status === 'Active' ? 'checkCircle' : 'shield',
                    'actor_user_id' => $actor['id'],
                    'actor_name' => User::fullName($actor),
                ],
            );

            NotificationService::send(
                (int) $target['id'],
                $title,
                $message,
                $status === 'Active' ? 'success' : 'warning',
                'user',
                (string) $target['public_id'],
            );
        });
    }

    /** @param array<string, mixed> $target @param array<string, mixed> $actor */
    public static function archiveAccount(array $target, array $actor): bool
    {
        return DB::transaction(function () use ($target, $actor): bool {
            $activeCoordinators = DB::table('users')
                ->select('id')
                ->where('role', 'Coordinator')
                ->where('status', 'Active')
                ->lockForUpdate()
                ->get();

            $current = DB::table('users')
                ->where('id', (int) $target['id'])
                ->lockForUpdate()
                ->first();
            $current = $current === null ? null : (array) $current;

            if ($current === null) {
                throw new NotFoundHttpException('The account no longer exists.');
            }

            if (!in_array($current['role'], ['Student', 'Coordinator'], true)
                || (int) $current['id'] === (int) $actor['id']
                || $current['status'] === 'Archived') {
                throw new ForbiddenHttpException();
            }

            if ($current['role'] === 'Coordinator' && $current['status'] === 'Active') {
                if (count($activeCoordinators) <= 1) {
                    return false;
                }
            }

            User::update((int) $current['id'], [
                'status' => 'Archived',
                'archived_status' => $current['status'],
            ]);
            RememberToken::clearForUser((int) $current['id']);

            AuditLogger::log(
                'user.archived',
                'Account Archived',
                User::fullName($current) . ' (' . $current['public_id'] . ', ' . $current['role']
                    . ') was archived. The profile and all history remain available for restoration.',
                [
                    'actor_user_id' => (int) $actor['id'],
                    'actor_name' => User::fullName($actor),
                    'target_user_id' => (int) $current['id'],
                    'entity_type' => 'user',
                    'entity_id' => (string) $current['public_id'],
                    'tone' => 'amber',
                    'icon' => 'archive',
                ],
            );

            return true;
        });
    }

    /** @param array<string, mixed> $target @param array<string, mixed> $actor */
    public static function restoreAccount(array $target, array $actor): void
    {
        DB::transaction(function () use ($target, $actor): void {
            $current = DB::table('users')
                ->where('id', (int) $target['id'])
                ->lockForUpdate()
                ->first();
            $current = $current === null ? null : (array) $current;

            if ($current === null) {
                throw new NotFoundHttpException('The account no longer exists.');
            }

            if (!in_array($current['role'], ['Student', 'Coordinator'], true)
                || (int) $current['id'] === (int) $actor['id']
                || $current['status'] !== 'Archived') {
                throw new ForbiddenHttpException();
            }

            $status = in_array($current['archived_status'] ?? null, ['Active', 'Pending', 'Inactive'], true)
                ? (string) $current['archived_status']
                : 'Inactive';

            User::update((int) $current['id'], [
                'status' => $status,
                'archived_status' => null,
            ]);

            AuditLogger::log(
                'user.restored',
                'Account Restored',
                User::fullName($current) . ' (' . $current['public_id'] . ', ' . $current['role']
                    . ') was restored with its previous ' . strtolower($status) . ' status.',
                [
                    'actor_user_id' => (int) $actor['id'],
                    'actor_name' => User::fullName($actor),
                    'target_user_id' => (int) $current['id'],
                    'entity_type' => 'user',
                    'entity_id' => (string) $current['public_id'],
                    'tone' => 'green',
                    'icon' => 'checkCircle',
                ],
            );
        });
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $file
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public static function attachSchoolId(array $target, array $file, array $actor): array
    {
        $oldFile = !empty($target['school_id_file_id']) ? File::find((int) $target['school_id_file_id']) : null;
        $stored = FileStorage::storeUpload(
            $file,
            'school-ids/' . (int) $target['id'],
            'user_school_id',
            (string) $target['id'],
            (int) $target['id'],
        );

        User::update((int) $target['id'], ['school_id_file_id' => (int) $stored['id']]);

        if ($oldFile !== null) {
            FileStorage::delete($oldFile);
        }

        AuditLogger::log(
            'user.school_id_uploaded',
            'School ID Uploaded',
            'A school ID document was uploaded for ' . User::fullName($target) . ' (' . $target['public_id'] . ').',
            [
                'target_user_id' => (int) $target['id'],
                'entity_type' => 'file',
                'entity_id' => (string) $stored['id'],
                'tone' => 'blue',
                'icon' => 'upload',
                'actor_user_id' => $actor['id'],
                'actor_name' => User::fullName($actor),
            ],
        );

        return $stored;
    }

    /** @param array<string, mixed> $target @param array<string, mixed> $actor */
    public static function sendPasswordReset(array $target, array $actor): void
    {
        $token = PasswordReset::createForUser($target);
        Mailer::send(
            (string) $target['email'],
            'Reset your BEACON OSAS password',
            "Hello " . User::fullName($target) . ",\n\n"
            . "An OSAS staff member started a password reset for your portal account.\n"
            . "Use the link below to choose a new password. It expires in 15 minutes.\n\n"
            . absolute_url('/reset-password/' . $token) . "\n\n"
            . "If you did not expect this, contact OSAS Support.\n",
        );

        AuditLogger::log(
            'user.password_reset_sent',
            'Password Reset Sent',
            'A password reset link was generated for ' . $target['email'] . '.',
            [
                'target_user_id' => (int) $target['id'],
                'entity_type' => 'user',
                'entity_id' => (string) $target['public_id'],
                'tone' => 'amber',
                'icon' => 'mail',
                'actor_user_id' => $actor['id'],
                'actor_name' => User::fullName($actor),
            ],
        );

        NotificationService::send(
            (int) $target['id'],
            'Password Reset Requested',
            'OSAS Support generated a password reset link for your account. Check your university email.',
            'info',
            'user',
            (string) $target['public_id'],
        );
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private static function normalize(array $data): array
    {
        $role = (string) ($data['role'] ?? 'Student');
        $status = (string) ($data['status'] ?? 'Pending');
        $data['first_name'] = trim((string) ($data['first_name'] ?? ''));
        $data['last_name'] = trim((string) ($data['last_name'] ?? ''));
        $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $data['role'] = $role;
        $data['status'] = $status;

        // Optional profile fields are normalised only when the request
        // actually carried them: an absent field (e.g. an input the edit
        // form disables for non-student targets) means "leave the stored
        // value unchanged", never "erase it".
        if (array_key_exists('middle_name', $data)) {
            $data['middle_name'] = trim((string) $data['middle_name']) ?: null;
        }

        if (array_key_exists('username', $data)) {
            $data['username'] = trim((string) $data['username']);
        }

        if (array_key_exists('department', $data)) {
            $data['department'] = trim((string) $data['department']) ?: null;
        }

        if (array_key_exists('phone', $data)) {
            $data['phone'] = trim((string) $data['phone']) ?: null;
        }

        if (array_key_exists('age', $data)) {
            $age = ($data['age'] === null || $data['age'] === '') ? null : (int) $data['age'];
            $data['age'] = ($role === 'Student' || $role === 'Coordinator') ? $age : null;
        }

        if ($role === 'Student') {
            $data['account_tier'] = 'Student Tier';

            if (array_key_exists('student_id', $data)) {
                $data['student_id'] = trim((string) $data['student_id']) ?: null;
            }

            if (array_key_exists('year_level', $data)) {
                $data['year_level'] = trim((string) $data['year_level']) ?: null;
            }

            if (array_key_exists('program', $data)) {
                $data['program'] = trim((string) $data['program']) ?: null;
            }
        } else {
            $data['account_tier'] = 'Admin Tier';
            // Staff accounts carry no student attributes: clear them so a
            // demoted student does not keep stale student data.
            $data['student_id'] = null;
            $data['year_level'] = null;
            $data['program'] = null;
        }

        $data['access_level'] = (string) config('people.access_levels.' . $role, '');

        return $data;
    }
}
