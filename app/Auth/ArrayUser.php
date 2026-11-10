<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Internal wrapper that presents the portal's array-style user rows (see
 * App\Models\User) through the native Authenticatable contract.
 *
 * Application code never touches this class directly — it reads plain arrays
 * via the auth_user() helper, which unwraps it with toArray(). Keeping the
 * wrapper internal keeps every existing $actor['id'] / $actor['role'] call
 * site array-typed.
 */
final class ArrayUser implements Authenticatable
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->attributes['id'] ?? null;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): mixed
    {
        return $this->attributes['password_hash'] ?? null;
    }

    /**
     * The portal manages remember-me itself (beacon_remember cookie backed by
     * the remember_tokens table), so the native recaller token is always empty —
     * an empty token also makes SessionGuard skip its own token cycling.
     */
    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // Intentionally a no-op: see getRememberToken().
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
