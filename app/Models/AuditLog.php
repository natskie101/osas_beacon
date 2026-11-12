<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class AuditLog
{
    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 10): array
    {
        $limit = max(1, min(200, $limit));

        return DB::table('audit_log as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.actor_user_id')
            ->leftJoin('users as t', 't.id', '=', 'a.target_user_id')
            ->select('a.*')
            ->selectRaw("COALESCE(CONCAT(u.first_name, ' ', u.last_name), a.actor_name) AS actor_display,
                    COALESCE(CONCAT(t.first_name, ' ', t.last_name), '') AS target_display")
            ->orderBy('a.created_at', 'desc')
            ->orderBy('a.id', 'desc')
            ->limit($limit)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(200, $limit));

        return DB::table('audit_log as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.actor_user_id')
            ->where(static function ($group) use ($userId): void {
                $group->where('a.actor_user_id', $userId)
                    ->orWhere('a.target_user_id', $userId);
            })
            ->select('a.*')
            ->selectRaw("COALESCE(CONCAT(u.first_name, ' ', u.last_name), a.actor_name) AS actor_display")
            ->orderBy('a.created_at', 'desc')
            ->orderBy('a.id', 'desc')
            ->limit($limit)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public static function count(): int
    {
        return (int) DB::table('audit_log')->count();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int, from: int, to: int}
     */
    public static function paginate(array $filters, int $page = 1, int $perPage = 15): array
    {
        $search = trim((string) ($filters['search'] ?? ''));

        $applyFilters = static function ($query) use ($filters, $search): void {
            if ($search !== '') {
                $like = '%' . \App\Support\Str::escapeLike($search) . '%';

                $query->where(static function ($group) use ($like): void {
                    $group->where('a.actor_name', 'like', $like)
                        ->orWhere('a.action', 'like', $like)
                        ->orWhere('a.title', 'like', $like)
                        ->orWhere('a.text', 'like', $like)
                        ->orWhere('a.entity_id', 'like', $like);
                });
            }

            $action = trim((string) ($filters['action'] ?? ''));

            if ($action !== '') {
                $query->where('a.action', $action);
            }
        };

        $query = DB::table('audit_log as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.actor_user_id')
            ->leftJoin('users as t', 't.id', '=', 'a.target_user_id');
        $applyFilters($query);
        $total = (int) $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $rows = $query
            ->select('a.*')
            ->selectRaw("COALESCE(CONCAT(u.first_name, ' ', u.last_name), a.actor_name) AS actor_display,
                    COALESCE(CONCAT(t.first_name, ' ', t.last_name), '') AS target_display")
            ->orderBy('a.created_at', 'desc')
            ->orderBy('a.id', 'desc')
            ->limit($perPage)
            ->offset($offset)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();

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
        $rows = DB::table('audit_log')
            ->where('action', '<>', '')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();

        return array_map(static fn (mixed $action): string => (string) $action, $rows);
    }
}
