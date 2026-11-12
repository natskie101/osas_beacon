<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class File
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $row = DB::table('files')->where('id', $id)->first();

        return $row === null ? null : (array) $row;
    }

    /** @return array<string, mixed>|null */
    public static function findForEntity(int $id, string $entityType, string $entityId): ?array
    {
        $row = DB::table('files')
            ->where('id', $id)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        return $row === null ? null : (array) $row;
    }

    /** @return array<string, mixed>|null */
    public static function latestForEntity(string $entityType, string $entityId): ?array
    {
        $row = DB::table('files')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('id', 'desc')
            ->first();

        return $row === null ? null : (array) $row;
    }
}
