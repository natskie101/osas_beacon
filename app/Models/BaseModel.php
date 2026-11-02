<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

/**
 * Small table-backed model for the content and service modules.
 *
 * It intentionally supports only the query shapes used by this portal:
 * lookup by public ID, filtered pagination, insert, update and delete.
 */
abstract class BaseModel
{
    protected static string $table = '';

    protected static string $prefix = 'REC';

    /** @var list<string> */
    protected static array $fillable = [];

    /** @var list<string> */
    protected static array $searchable = [];

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return DB::first('SELECT * FROM ' . static::$table . ' WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByPublicId(string $publicId): ?array
    {
        return DB::first(
            'SELECT * FROM ' . static::$table . ' WHERE public_id = ? LIMIT 1',
            [$publicId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function all(string $order = 'created_at DESC, id DESC'): array
    {
        return DB::select('SELECT * FROM ' . static::$table . ' ORDER BY ' . $order);
    }

    public static function count(?string $where = null, array $bindings = []): int
    {
        return (int) DB::value(
            'SELECT COUNT(*) FROM ' . static::$table . ($where !== null ? ' WHERE ' . $where : ''),
            $bindings,
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data, ?string $publicId = null): int
    {
        $now = date('Y-m-d H:i:s');
        $columns = [];
        $placeholders = [];
        $bindings = [];

        foreach (static::$fillable as $column) {
            if (array_key_exists($column, $data)) {
                $columns[] = '`' . $column . '`';
                $placeholders[] = '?';
                $bindings[] = $data[$column];
            }
        }

        $columns[] = '`public_id`';
        $placeholders[] = '?';
        $bindings[] = $publicId ?? static::nextPublicId();
        $columns[] = '`created_at`';
        $placeholders[] = '?';
        $bindings[] = $data['created_at'] ?? $now;
        $columns[] = '`updated_at`';
        $placeholders[] = '?';
        $bindings[] = $data['updated_at'] ?? $now;

        return DB::insert(
            'INSERT INTO ' . static::$table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
            $bindings,
        );
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data, bool $touch = true): void
    {
        $sets = [];
        $bindings = [];

        foreach (static::$fillable as $column) {
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
        DB::update('UPDATE ' . static::$table . ' SET ' . implode(', ', $sets) . ' WHERE id = ?', $bindings);
    }

    public static function delete(int $id): void
    {
        DB::delete('DELETE FROM ' . static::$table . ' WHERE id = ?', [$id]);
    }

    /**
     * @param array<string, mixed> $filters
     * @param list<string> $statuses
     * @param array<string, string> $columnFilters
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int, from: int, to: int}
     */
    public static function paginate(
        array $filters,
        int $page = 1,
        int $perPage = 12,
        array $statuses = [],
        string $order = 'newest',
        array $columnFilters = [],
    ): array {
        $where = [];
        $bindings = [];
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '' && static::$searchable !== []) {
            $parts = [];
            $like = '%' . $search . '%';

            foreach (static::$searchable as $column) {
                $parts[] = '`' . $column . '` LIKE ?';
                $bindings[] = $like;
            }

            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        $status = (string) ($filters['status'] ?? '');

        if ($status !== '' && in_array($status, $statuses, true)) {
            $where[] = '`status` = ?';
            $bindings[] = $status;
        }

        foreach ($columnFilters as $filterKey => $column) {
            $value = trim((string) ($filters[$filterKey] ?? ''));

            if ($value !== '') {
                $where[] = '`' . $column . '` = ?';
                $bindings[] = $value;
            }
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $total = (int) DB::value('SELECT COUNT(*) FROM ' . static::$table . $whereSql, $bindings);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $orderSql = match ($order) {
            'oldest' => 'created_at ASC, id ASC',
            'title' => 'title ASC, id ASC',
            'views' => 'views DESC, id DESC',
            'date' => 'COALESCE(published_at, scheduled_for, created_at) DESC, id DESC',
            default => 'created_at DESC, id DESC',
        };

        $data = DB::select(
            'SELECT * FROM ' . static::$table . $whereSql . ' ORDER BY ' . $orderSql
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
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

    protected static function nextPublicId(): string
    {
        $year = date('Y');
        $prefix = preg_quote(static::$prefix, '/');
        $rows = DB::select(
            'SELECT public_id FROM ' . static::$table . ' WHERE public_id LIKE ?',
            [static::$prefix . '-%'],
        );
        $max = 0;
        $usesYear = null;

        foreach ($rows as $row) {
            $publicId = (string) ($row['public_id'] ?? '');

            if (preg_match('/^' . $prefix . '-(?:(\d{4})-)?(\d+)$/', $publicId, $matches) !== 1) {
                continue;
            }

            $rowYear = $matches[1] !== '' ? (int) $matches[1] : null;

            if ($rowYear !== null && $rowYear !== (int) $year) {
                continue;
            }

            $number = (int) $matches[2];

            if ($number >= $max) {
                $max = $number;
                $usesYear = $rowYear !== null;
            }
        }

        $format = $usesYear === false ? static::$prefix . '-%03d' : static::$prefix . '-' . $year . '-%03d';

        return sprintf($format, $max + 1);
    }

    /** @param mixed $value @return list<mixed> */
    public static function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
