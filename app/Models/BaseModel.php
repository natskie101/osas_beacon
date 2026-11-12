<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Small table-backed model for the content and service modules.
 *
 * It intentionally supports only the query shapes used by this portal:
 * lookup by public ID, filtered pagination, insert, update and delete.
 *
 * Data access goes through Laravel's standard query builder; results are
 * converted back to plain arrays so controllers and views keep their
 * array-shape contract.
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
        $row = DB::table(static::$table)->where('id', $id)->first();

        return $row === null ? null : (array) $row;
    }

    /** @return array<string, mixed>|null */
    public static function findByPublicId(string $publicId): ?array
    {
        $row = DB::table(static::$table)->where('public_id', $publicId)->first();

        return $row === null ? null : (array) $row;
    }

    /** @return list<array<string, mixed>> */
    public static function all(string $order = 'created_at DESC, id DESC'): array
    {
        return DB::table(static::$table)
            ->orderByRaw($order)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public static function count(?string $where = null, array $bindings = []): int
    {
        $query = DB::table(static::$table);

        if ($where !== null) {
            $query->whereRaw($where, $bindings);
        }

        return (int) $query->count();
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data, ?string $publicId = null): int
    {
        $now = date('Y-m-d H:i:s');
        $row = [];

        foreach (static::$fillable as $column) {
            if (array_key_exists($column, $data)) {
                $row[$column] = $data[$column];
            }
        }

        $row['public_id'] = $publicId ?? static::nextPublicId();
        $row['created_at'] = $data['created_at'] ?? $now;
        $row['updated_at'] = $data['updated_at'] ?? $now;

        return (int) DB::table(static::$table)->insertGetId($row);
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data, bool $touch = true): void
    {
        $sets = [];

        foreach (static::$fillable as $column) {
            if (array_key_exists($column, $data)) {
                $sets[$column] = $data[$column];
            }
        }

        if ($sets === []) {
            return;
        }

        if ($touch) {
            $sets['updated_at'] = date('Y-m-d H:i:s');
        }

        DB::table(static::$table)->where('id', $id)->update($sets);
    }

    public static function delete(int $id): void
    {
        DB::table(static::$table)->where('id', $id)->delete();
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
        $search = trim((string) ($filters['search'] ?? ''));
        $searchable = static::$searchable;

        $applyFilters = static function ($query) use ($filters, $statuses, $columnFilters, $search, $searchable): void {
            if ($search !== '' && $searchable !== []) {
                $like = '%' . Str::escapeLike($search) . '%';

                $query->where(static function ($group) use ($searchable, $like): void {
                    foreach ($searchable as $column) {
                        $group->orWhere($column, 'like', $like);
                    }
                });
            }

            $status = (string) ($filters['status'] ?? '');

            if ($status !== '' && in_array($status, $statuses, true)) {
                $query->where('status', $status);
            }

            foreach ($columnFilters as $filterKey => $column) {
                $value = trim((string) ($filters[$filterKey] ?? ''));

                if ($value !== '') {
                    $query->where($column, $value);
                }
            }
        };

        $query = DB::table(static::$table);
        $applyFilters($query);
        $total = (int) $query->count();
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

        $rows = $query
            ->orderByRaw($orderSql)
            ->limit($perPage)
            ->offset($offset)
            ->get();

        return [
            'data' => $rows->map(static fn (object $row): array => (array) $row)->all(),
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
        $publicIds = DB::table(static::$table)
            ->where('public_id', 'like', static::$prefix . '-%')
            ->pluck('public_id');
        $max = 0;
        $usesYear = null;

        foreach ($publicIds as $publicId) {
            $publicId = (string) $publicId;

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
