<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class AuditLog
{
    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 10): array
    {
        $limit = max(1, min(200, $limit));

        return DB::select(
            'SELECT a.*,
                    COALESCE(CONCAT(u.first_name, " ", u.last_name), a.actor_name) AS actor_display,
                    COALESCE(CONCAT(t.first_name, " ", t.last_name), "") AS target_display
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.actor_user_id
             LEFT JOIN users t ON t.id = a.target_user_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT ' . $limit,
        );
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(200, $limit));

        return DB::select(
            'SELECT a.*,
                    COALESCE(CONCAT(u.first_name, " ", u.last_name), a.actor_name) AS actor_display
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.actor_user_id
             WHERE a.actor_user_id = ? OR a.target_user_id = ?
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT ' . $limit,
            [$userId, $userId],
        );
    }

    public static function count(): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM audit_log');
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int, from: int, to: int}
     */
    public static function paginate(array $filters, int $page = 1, int $perPage = 15): array
    {
        $where = [];
        $bindings = [];
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(a.actor_name LIKE ? OR a.action LIKE ? OR a.title LIKE ? OR a.text LIKE ? OR a.entity_id LIKE ?)';
            array_push($bindings, $like, $like, $like, $like, $like);
        }

        $action = trim((string) ($filters['action'] ?? ''));

        if ($action !== '') {
            $where[] = 'a.action = ?';
            $bindings[] = $action;
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $total = (int) DB::value('SELECT COUNT(*) FROM audit_log a' . $whereSql, $bindings);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;
        $rows = DB::select(
            'SELECT a.*,
                    COALESCE(CONCAT(u.first_name, " ", u.last_name), a.actor_name) AS actor_display,
                    COALESCE(CONCAT(t.first_name, " ", t.last_name), "") AS target_display
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.actor_user_id
             LEFT JOIN users t ON t.id = a.target_user_id'
            . $whereSql
            . ' ORDER BY a.created_at DESC, a.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $bindings,
        );

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /** @return list<string> */
    public static function actions(): array
    {
        $rows = DB::select('SELECT DISTINCT action FROM audit_log WHERE action <> "" ORDER BY action ASC');

        return array_map(static fn (array $row): string => (string) $row['action'], $rows);
    }
}
