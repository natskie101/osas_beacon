<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Support\Exceptions\ForbiddenHttpException;

final class UserPolicy
{
    /** @param array<string, mixed>|null $actor @param array<string, mixed> $target */
    public static function allows(string $ability, ?array $actor, array $target = []): bool
    {
        if ($actor === null) {
            return false;
        }

        $actorRole = Role::tryFrom((string) ($actor['role'] ?? ''));

        if ($actorRole === null) {
            return false;
        }

        $targetRole = Role::tryFrom((string) ($target['role'] ?? ''));

        if ($targetRole !== null && (int) ($actor['id'] ?? 0) === (int) ($target['id'] ?? 0) && $ability === 'view') {
            return true;
        }

        if ($actorRole === Role::Coordinator) {
            return true;
        }

        if ($actorRole !== Role::Administrator) {
            return false;
        }

        if ($ability === 'view') {
            return true;
        }

        if ($targetRole !== Role::Student) {
            return false;
        }

        return in_array($ability, ['manage', 'verify', 'reset_password', 'upload_school_id', 'download_school_id'], true);
    }

    /** @param array<string, mixed>|null $actor @param array<string, mixed> $target */
    public static function authorize(string $ability, ?array $actor, array $target = []): void
    {
        if (!self::allows($ability, $actor, $target)) {
            throw new ForbiddenHttpException();
        }
    }

    public static function canManageAccounts(?array $actor): bool
    {
        $role = Role::tryFrom((string) ($actor['role'] ?? ''));

        return $role !== null && $role->isStaff();
    }

    /** @return list<string> */
    public static function assignableRoles(?array $actor): array
    {
        $role = Role::tryFrom((string) ($actor['role'] ?? ''));

        if ($role === Role::Coordinator) {
            return ['Student', 'Administrator', 'Coordinator'];
        }

        if ($role === Role::Administrator) {
            return ['Student'];
        }

        return [];
    }

    public static function canAssignRole(?array $actor, string $role): bool
    {
        return in_array($role, self::assignableRoles($actor), true);
    }
}
