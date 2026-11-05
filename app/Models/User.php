<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;
use App\Support\Str;

final class User
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return DB::first('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        return DB::first('SELECT * FROM users WHERE email = ? LIMIT 1', [mb_strtolower(trim($email))]);
    }

    /** @return array<string, mixed>|null */
    public static function findByPublicId(string $publicId): ?array
    {
        return DB::first('SELECT * FROM users WHERE public_id = ? LIMIT 1', [$publicId]);
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return DB::select('SELECT * FROM users ORDER BY last_name ASC, first_name ASC');
    }

    /** @return list<array<string, mixed>> */
    public static function byRole(string $role): array
    {
        return DB::select('SELECT * FROM users WHERE role = ? ORDER BY last_name ASC, first_name ASC', [$role]);
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

        $id = DB::insert(
            'INSERT INTO users
                (public_id, first_name, middle_name, last_name, email, username, password_hash, role, student_id, age,
                 department, program, year_level, access_level, account_tier, status, phone,
                 email_verified_at, created_at, updated_at, requested_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                'TMP-' . Str::random(6),
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
                mb_strtolower((string) $data['email']),
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $data['role'] ?? 'Student',
                $data['student_id'] ?? null,
                $data['age'] ?? null,
                $data['department'] ?? null,
                $data['program'] ?? null,
                $data['year_level'] ?? null,
                $data['access_level'] ?? null,
                $data['account_tier'] ?? 'Student Tier',
                $data['status'] ?? 'Pending',
                $data['phone'] ?? null,
                ($data['status'] ?? 'Pending') === 'Active' ? $now : null,
                $now,
                $now,
                $data['requested_at'] ?? $now,
            ],
        );

        $publicId = 'USR-' . str_pad((string) (1000 + $id), 4, '0', STR_PAD_LEFT);
        self::update($id, ['public_id' => $publicId], false);

        return $id;
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data, bool $touch = true): void
    {
        $allowed = [
            'public_id', 'first_name', 'middle_name', 'last_name', 'email', 'username', 'role', 'student_id', 'age',
            'department', 'program', 'year_level', 'access_level', 'account_tier', 'status',
            'phone', 'school_id_file_id', 'last_login_at', 'last_seen_at', 'email_verified_at',
            'requested_at',
        ];

        $sets = [];
        $bindings = [];

        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $sets[] = '`' . $column . '` = ?';
                $bindings[] = $data[$column];
            }
        }

        if ($sets === []) {
            return;
        }

        if ($touch) {
            $sets[] = '`updated_at` = ?';
            $bindings[] = date('Y-m-d H:i:s');
        }

        $bindings[] = $id;
        DB::update('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $bindings);
    }

    public static function updatePassword(int $id, string $plainPassword): void
    {
        DB::update(
            'UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?',
            [password_hash($plainPassword, PASSWORD_DEFAULT), date('Y-m-d H:i:s'), $id],
        );
    }

    public static function recordLogin(int $id): void
    {
        $now = date('Y-m-d H:i:s');
        DB::update('UPDATE users SET last_login_at = ?, last_seen_at = ?, updated_at = ? WHERE id = ?', [$now, $now, $now, $id]);
    }

    public static function touchSeen(int $id): void
    {
        DB::update('UPDATE users SET last_seen_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $id]);
    }

<<<<<<< HEAD
    /** @return array{total: int, students: int, active: int, active_students: int, signed_in_students: int, pending: int, inactive: int, staff: int, coordinators: int, administrators: int, latest_created_at: string|null} */
=======
    /** @return array{total: int, students: int, active: int, pending: int, inactive: int, staff: int} */
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT
                COUNT(*) AS total,
                SUM(role = "Student") AS students,
                SUM(status = "Active") AS active,
<<<<<<< HEAD
                SUM(role = "Student" AND status = "Active") AS active_students,
                SUM(role = "Student" AND last_login_at IS NOT NULL) AS signed_in_students,
                SUM(status = "Pending") AS pending,
                SUM(status = "Inactive") AS inactive,
                SUM(role <> "Student") AS staff,
                SUM(role = "Coordinator") AS coordinators,
                SUM(role = "Administrator") AS administrators,
                MAX(created_at) AS latest_created_at
=======
                SUM(status = "Pending") AS pending,
                SUM(status = "Inactive") AS inactive,
                SUM(role <> "Student") AS staff
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
             FROM users',
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'students' => (int) ($row['students'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
<<<<<<< HEAD
            'active_students' => (int) ($row['active_students'] ?? 0),
            'signed_in_students' => (int) ($row['signed_in_students'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'staff' => (int) ($row['staff'] ?? 0),
            'coordinators' => (int) ($row['coordinators'] ?? 0),
            'administrators' => (int) ($row['administrators'] ?? 0),
            'latest_created_at' => $row['latest_created_at'] ?? null,
=======
            'pending' => (int) ($row['pending'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'staff' => (int) ($row['staff'] ?? 0),
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        ];
    }

    /**
     * @param array{search?: string, role?: string, status?: string, group?: string, sort?: string, direction?: string} $filters
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int, from: int, to: int}
     */
    public static function paginate(array $filters, int $page = 1, int $perPage = 12): array
    {
        $where = [];
        $bindings = [];

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id LIKE ? OR public_id LIKE ?)';
            array_push($bindings, $like, $like, $like, $like, $like);
        }

        $role = (string) ($filters['role'] ?? '');

        if (in_array($role, ['Coordinator', 'Administrator', 'Student'], true)) {
            $where[] = 'role = ?';
            $bindings[] = $role;
        }

        $status = (string) ($filters['status'] ?? '');

        if (in_array($status, ['Active', 'Pending', 'Inactive'], true)) {
            $where[] = 'status = ?';
            $bindings[] = $status;
        }

        $group = (string) ($filters['group'] ?? '');

        if ($group === 'students') {
            $where[] = 'role = ?';
            $bindings[] = 'Student';
        } elseif ($group === 'staff') {
            $where[] = 'role <> ?';
            $bindings[] = 'Student';
        }

<<<<<<< HEAD
        if (!empty($filters['exclude_pending'])) {
            $where[] = 'status <> ?';
            $bindings[] = 'Pending';
        }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $total = (int) DB::value('SELECT COUNT(*) FROM users' . $whereSql, $bindings);
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

        $data = DB::select(
            'SELECT * FROM users' . $whereSql . ' ORDER BY ' . $orderBy . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $bindings,
        );

        return [
            'data' => $data,
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

        while ((int) DB::value(
            'SELECT COUNT(*) FROM users WHERE username = ?' . ($ignoreId !== null ? ' AND id <> ?' : ''),
            $ignoreId !== null ? [$candidate, $ignoreId] : [$candidate],
        ) > 0) {
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
