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

        if ($ability === 'archive' || $ability === 'restore') {
            return $actorRole === Role::Administrator
                && in_array($targetRole, [Role::Student, Role::Coordinator], true)
                && ($ability !== 'restore' || ($target['status'] ?? '') === 'Archived')
                && ($ability !== 'archive' || ($target['status'] ?? '') !== 'Archived')
                && (int) ($actor['id'] ?? 0) !== (int) ($target['id'] ?? 0);
        }

        if (($target['status'] ?? '') === 'Archived' && $ability !== 'view') {
            return false;
        }

        if ($actorRole === Role::Coordinator) {
            // Coordinators manage students and fellow coordinators, but an
            // Administrator account is outside their authority: they may view
            // it (directory, profile, activity) and never change it — no edit,
            // status change, role change, verification or password reset.
            return $targetRole !== Role::Administrator || $ability === 'view';
        }

        if ($actorRole !== Role::Administrator) {
            return false;
        }

        if ($ability === 'view') {
            return true;
        }

        // Students and Coordinators are within the Administrator's authority;
        // Administrator accounts stay view-only for everyone.
        if ($targetRole !== Role::Student && $targetRole !== Role::Coordinator) {
            return false;
        }

        return in_array($ability, ['manage', 'verify', 'reset_password', 'upload_school_id', 'download_school_id', 'change_role'], true);
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
            return ['Student', 'Coordinator'];
        }

        return [];
    }

    /** @return list<string> */
    public static function creatableRoles(?array $actor): array
    {
        $role = Role::tryFrom((string) ($actor['role'] ?? ''));

        if ($role === Role::Administrator) {
            return ['Student', 'Coordinator'];
        }

        if ($role === Role::Coordinator) {
            return ['Student'];
        }

        return [];
    }

    public static function canCreateRole(?array $actor, string $role): bool
    {
        return in_array($role, self::creatableRoles($actor), true);
    }

    public static function canAssignRole(?array $actor, string $role): bool
    {
        return in_array($role, self::assignableRoles($actor), true);
    }
}
