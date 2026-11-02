<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Broadcast extends BaseModel
{
    protected static string $table = 'broadcasts';
    protected static string $prefix = 'NOTIF';
    protected static array $fillable = [
        'subject', 'message', 'channel', 'status', 'audience', 'scheduled_at', 'sent_at',
        'reach', 'delivered', 'opened', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'subject', 'message', 'audience'];

    /** @return array{total:int,draft:int,pending:int,scheduled:int,sent:int,reach:int,opened:int} */
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Draft") AS draft,
                    SUM(status = "Pending") AS pending,
                    SUM(status = "Scheduled") AS scheduled,
                    SUM(status = "Sent") AS sent,
                    COALESCE(SUM(reach), 0) AS reach,
                    COALESCE(SUM(opened), 0) AS opened
             FROM broadcasts',
        );

        return array_map('intval', $row ?? []);
    }

    /** @return list<array<string, mixed>> */
    public static function forStudent(?string $audience = null): array
    {
        $sql = 'SELECT * FROM broadcasts WHERE status = "Sent"';
        $bindings = [];

        if ($audience !== null && $audience !== '') {
            $sql .= ' AND (audience LIKE ? OR audience LIKE "All%")';
            $bindings[] = '%' . $audience . '%';
        }

        return DB::select($sql . ' ORDER BY COALESCE(sent_at, created_at) DESC, id DESC', $bindings);
    }
}
