<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;
use App\Support\Str;

final class PasswordReset
{
    /** @param array<string, mixed> $user */
    public static function createForUser(array $user): string
    {
        self::deleteForEmail((string) $user['email']);

        $token = Str::random(32);

        DB::insert(
            'INSERT INTO password_resets (user_id, email, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?)',
            [
                (int) $user['id'],
                (string) $user['email'],
                hash('sha256', $token),
                date('Y-m-d H:i:s', time() + 900),
                date('Y-m-d H:i:s'),
            ],
        );

        return $token;
    }

    /** @return array<string, mixed>|null */
    public static function findValid(string $token): ?array
    {
        $row = DB::first(
            'SELECT pr.*, u.first_name, u.last_name, u.email
             FROM password_resets pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = ?
             LIMIT 1',
            [hash('sha256', $token)],
        );

        if ($row === null) {
            return null;
        }

        if ($row['used_at'] !== null || strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }

    public static function markUsed(int $id): void
    {
        DB::update('UPDATE password_resets SET used_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $id]);
    }

    public static function deleteForEmail(string $email): void
    {
        DB::delete('DELETE FROM password_resets WHERE email = ?', [$email]);
    }
}
