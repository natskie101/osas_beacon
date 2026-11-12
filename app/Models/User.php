<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Str;
use Illuminate\Support\Facades\DB;

final class User
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $row = DB::table('users')->where('id', $id)->first();

        return $row === null ? null : (array) $row;
    }

    /** @return array<string, mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        $row = DB::table('users')
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        return $row === null ? null : (array) $row;
    }

    /** @return array<string, mixed>|null */
    public static function findByPublicId(string $publicId): ?array
    {
        $row = DB::table('users')->where('public_id', $publicId)->first();

        return $row === null ? null : (array) $row;
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return DB::table('users')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function byRole(string $role): array
    {
        return DB::table('users')
            ->where('role', $role)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $password = (string) ($data['password'] ?? Str::random(16));
        $username = trim((string) ($data['username'] ?? ''));

        if ($username === '') {
            $username = Str::slug((string) strtok((string) $data['email'], '@'));
        }

        $username = self::uniqueUsername($username);

        $id = (int) DB::table('users')->insertGetId([
            'public_id' => 'TMP-' . Str::random(6),
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'email' => mb_strtolower((string) $data['email']),
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'Student',
            'student_id' => $data['student_id'] ?? null,
            'age' => $data['age'] ?? null,
            'department' => $data['department'] ?? null,
            'program' => $data['program'] ?? null,
            'year_level' => $data['year_level'] ?? null,
            'access_level' => $data['access_level'] ?? null,
            'account_tier' => $data['account_tier'] ?? 'Student Tier',
            'status' => $data['status'] ?? 'Pending',
            'phone' => $data['phone'] ?? null,
            'email_verified_at' => ($data['status'] ?? 'Pending') === 'Active' ? $now : null,
            'created_at' => $now,
            'updated_at' => $now,
            'requested_at' => $data['requested_at'] ?? $now,
        ]);

        $publicId = 'USR-' . str_pad((string) (1000 + $id), 4, '0', STR_PAD_LEFT);
        self::update($id, ['public_id' => $publicId], false);

        return $id;
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data, bool $touch = true): void
    {
        $allowed = [
            'public_id', 'first_name', 'middle_name', 'last_name', 'email', 'username', 'role', 'student_id', 'age',
            'department', 'program', 'year_level', 'access_level', 'account_tier', 'status', 'archived_status',
            'phone', 'school_id_file_id', 'last_login_at', 'last_seen_at', 'email_verified_at',
            'requested_at',
        ];

        $sets = [];

        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $sets[$column] = $data[$column];
            }
        }

        if ($sets === []) {
            return;
        }

        if ($touch) {
            $sets['updated_at'] = date('Y-m-d H:i:s');
        }

        DB::table('users')->where('id', $id)->update($sets);
    }

    public static function updatePassword(int $id, string $plainPassword): void
    {
        DB::table('users')->where('id', $id)->update([
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function recordLogin(int $id): void
    {
        $now = date('Y-m-d H:i:s');
        DB::table('users')->where('id', $id)->update([
            'last_login_at' => $now,
            'last_seen_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public static function touchSeen(int $id): void
    {
        DB::table('users')->where('id', $id)->update([
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array{total: int, students: int, active: int, active_students: int, signed_in_students: int, pending: int, inactive: int, staff: int, coordinators: int, administrators: int, latest_created_at: string|null} */
    public static function counts(): array
    {
        $row = DB::table('users')
            ->selectRaw('COUNT(*) AS total,
                    SUM(role = "Student") AS students,
                    SUM(status = "Active") AS active,
                    SUM(role = "Student" AND status = "Active") AS active_students,
                    SUM(role = "Student" AND last_login_at IS NOT NULL) AS signed_in_students,
                    SUM(status = "Pending") AS pending,
                    SUM(status = "Inactive") AS inactive,
                    SUM(role <> "Student") AS staff,
                    SUM(role = "Coordinator") AS coordinators,
                    SUM(role = "Administrator") AS administrators,
                    MAX(created_at) AS latest_created_at')
            ->first();
        $row = $row === null ? [] : (array) $row;

        return [
            'total' => (int) ($row['total'] ?? 0),
            'students' => (int) ($row['students'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'active_students' => (int) ($row['active_students'] ?? 0),
            'signed_in_students' => (int) ($row['signed_in_students'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'staff' => (int) ($row['staff'] ?? 0),
            'coordinators' => (int) ($row['coordinators'] ?? 0),
            'administrators' => (int) ($row['administrators'] ?? 0),
            'latest_created_at' => $row['latest_created_at'] ?? null,
        ];
    }

    /**
     * @param array{search?: string, role?: string, status?: string, group?: string, sort?: string, direction?: string} $filters
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int, from: int, to: int}
     */
    public static function paginate(array $filters, int $page = 1, int $perPage = 12): array
    {
        $search = trim((string) ($filters['search'] ?? ''));

        $applyFilters = static function ($query) use ($filters, $search): void {
            if ($search !== '') {
                $like = '%' . Str::escapeLike($search) . '%';

                $query->where(static function ($group) use ($like): void {
                    $group->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('student_id', 'like', $like)
                        ->orWhere('public_id', 'like', $like);
                });
            }

            $role = (string) ($filters['role'] ?? '');

            if (in_array($role, ['Coordinator', 'Administrator', 'Student'], true)) {
                $query->where('role', $role);
            }

            $status = (string) ($filters['status'] ?? '');

            if (in_array($status, ['Active', 'Pending', 'Inactive', 'Archived'], true)) {
                $query->where('status', $status);
            }

            $group = (string) ($filters['group'] ?? '');

            if ($group === 'students') {
                $query->where('role', 'Student');
            } elseif ($group === 'staff') {
                $query->where('role', '<>', 'Student');
            }

            if (!empty($filters['exclude_pending'])) {
                $query->whereNotIn('status', ['Pending', 'Archived']);
            }
        };

        $query = DB::table('users');
        $applyFilters($query);
        $total = (int) $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $sort = (string) ($filters['sort'] ?? 'name');
        $direction = strtolower((string) ($filters['direction'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $orderBy = match ($sort) {
            'created' => 'created_at ' . $direction,
            'last_login' => 'last_login_at ' . $direction,
            'status' => 'status ' . $direction . ', last_name ASC',
            'role' => 'role ' . $direction . ', last_name ASC',
            default => 'last_name ' . $direction . ', first_name ' . $direction,
        };

        $rows = $query
            ->orderByRaw($orderBy)
            ->limit($perPage)
            ->offset($offset)
            ->get();

        return [
            'data' => $rows->map(static fn (object $row): array => (array) $row)->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    private static function uniqueUsername(string $base, ?int $ignoreId = null): string
    {
        $base = Str::slug($base);

        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $suffix = 1;

        while (DB::table('users')
            ->where('username', $candidate)
            ->when($ignoreId !== null, static fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists()
        ) {
            $candidate = $base . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    /** @param array<string, mixed> $user */
    public static function fullName(array $user): string
    {
        return trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
    }
}
