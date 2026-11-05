<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class File
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return DB::first('SELECT * FROM files WHERE id = ? LIMIT 1', [$id]);
    }
}
