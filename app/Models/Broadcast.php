<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

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
        $row = DB::table('broadcasts')
            ->selectRaw('COUNT(*) AS total,
                    SUM(status = "Draft") AS draft,
                    SUM(status = "Pending") AS pending,
                    SUM(status = "Scheduled") AS scheduled,
                    SUM(status = "Sent") AS sent,
                    COALESCE(SUM(reach), 0) AS reach,
                    COALESCE(SUM(opened), 0) AS opened')
            ->first();

        return array_map('intval', $row === null ? [] : (array) $row);
    }

    /** @return list<array<string, mixed>> */
    public static function forStudent(?string $audience = null): array
    {
        $query = DB::table('broadcasts')->where('status', 'Sent');

        if ($audience !== null && $audience !== '') {
            $query->where(static function ($group) use ($audience): void {
                $group->where('audience', 'like', '%' . $audience . '%')
                    ->orWhere('audience', 'like', 'All%');
            });
        }

        return $query
            ->orderByRaw('COALESCE(sent_at, created_at) DESC, id DESC')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }
}
