<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Str;
use Illuminate\Support\Facades\DB;

final class PasswordReset
{
    /** @param array<string, mixed> $user */
    public static function createForUser(array $user): string
    {
        self::deleteForEmail((string) $user['email']);

        $token = Str::random(32);

        DB::table('password_resets')->insert([
            'user_id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 900),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /** @return array<string, mixed>|null */
    public static function findValid(string $token): ?array
    {
        $row = DB::table('password_resets as pr')
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->where('pr.token_hash', hash('sha256', $token))
            ->select('pr.*', 'u.first_name', 'u.last_name', 'u.email')
            ->first();

        if ($row === null) {
            return null;
        }

        $row = (array) $row;

        if ($row['used_at'] !== null || strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }

    public static function markUsed(int $id): void
    {
        DB::table('password_resets')->where('id', $id)->update([
            'used_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function deleteForEmail(string $email): void
    {
        DB::table('password_resets')->where('email', $email)->delete();
    }
}
