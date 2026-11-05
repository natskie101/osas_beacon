<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\Auth;
use App\Support\DB;

final class AuditLogger
{
    /**
     * @param array<string, mixed> $options
     */
    public static function log(string $action, string $title, string $text = '', array $options = []): void
    {
        $actor = Auth::user();
        $actorId = $options['actor_user_id'] ?? ($actor['id'] ?? null);
        $actorName = $options['actor_name'] ?? ($actor !== null ? User::fullName($actor) : '');

        DB::insert(
            'INSERT INTO audit_log
                (actor_user_id, actor_name, target_user_id, action, entity_type, entity_id,
                 title, text, tone, icon, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $actorId,
                $actorName,
                $options['target_user_id'] ?? null,
                $action,
                $options['entity_type'] ?? '',
                $options['entity_id'] ?? '',
                $title,
                $text,
                $options['tone'] ?? 'brand',
                $options['icon'] ?? 'info',
                $options['ip_address'] ?? null,
                $options['user_agent'] ?? null,
                date('Y-m-d H:i:s'),
            ],
        );
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user, string $ip, ?string $userAgent = null): void
    {
        self::log(
            'auth.login',
            'Portal Sign-In',
            User::fullName($user) . ' signed in to the ' . strtolower((string) $user['role']) . ' portal.',
            [
                'actor_user_id' => (int) $user['id'],
                'actor_name' => User::fullName($user),
                'target_user_id' => (int) $user['id'],
                'entity_type' => 'user',
                'entity_id' => (string) $user['public_id'],
                'tone' => 'brand',
                'icon' => 'lock',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ],
        );
    }

    /** @param array<string, mixed> $user */
    public static function logout(array $user, string $ip, ?string $userAgent = null): void
    {
        self::log(
            'auth.logout',
            'Portal Sign-Out',
            User::fullName($user) . ' signed out of the portal.',
            [
                'actor_user_id' => (int) $user['id'],
                'actor_name' => User::fullName($user),
                'target_user_id' => (int) $user['id'],
                'entity_type' => 'user',
                'entity_id' => (string) $user['public_id'],
                'tone' => 'brand',
                'icon' => 'logout',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ],
        );
    }
}
