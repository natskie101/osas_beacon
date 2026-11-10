<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * UserProvider backed by the array-style App\Models\User model.
 *
 * The Active-status gate lives here (mirroring App\Support\auth_user()):
 * any account that is no longer Active resolves to null, so inactive,
 * archived, pending and deleted accounts are treated as guests everywhere.
 */
final class ArrayUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?ArrayUser
    {
        $user = User::find((int) $identifier);

        if ($user === null || $user['status'] !== 'Active') {
            return null;
        }

        return new ArrayUser($user);
    }

    /** The portal never uses the native recaller token (see ArrayUser). */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?ArrayUser
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
        // Intentionally a no-op: remember-me is handled by App\Models\RememberToken.
    }

    /** @param array<string, mixed> $credentials */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?ArrayUser
    {
        $email = $credentials['email'] ?? null;

        if (!is_string($email) || $email === '') {
            return null;
        }

        $user = User::findByEmail($email);

        return $user === null ? null : new ArrayUser($user);
    }

    /** @param array<string, mixed> $credentials */
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        if (!$user instanceof ArrayUser || !is_string($password)) {
            return false;
        }

        $hash = (string) ($user->toArray()['password_hash'] ?? '');

        return $hash !== '' && password_verify($password, $hash);
    }

    /** The portal does not auto-rehash on login (legacy parity). */
    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): bool
    {
        return false;
    }
}
