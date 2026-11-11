<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class Infographic
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $rows = DB::table('dashboard_infographics')->orderBy('id')->get();

        return $rows->map(static fn (mixed $row): array => (array) $row)->all();
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $row = DB::table('dashboard_infographics')->where('id', $id)->first();

        return $row === null ? null : (array) $row;
    }

    public static function create(int $imageFileId, int $createdBy): int
    {
        return (int) DB::table('dashboard_infographics')->insertGetId([
            'image_file_id' => $imageFileId,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function delete(int $id): void
    {
        DB::table('dashboard_infographics')->where('id', $id)->delete();
    }
}
