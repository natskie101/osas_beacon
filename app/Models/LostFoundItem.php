<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class LostFoundItem extends BaseModel
{
    protected static string $table = 'lost_found_items';
    protected static string $prefix = 'LF';
    protected static array $fillable = [
        'type', 'title', 'category', 'description', 'location', 'date_occurred', 'reported_by',
        'reporter_email', 'student_id', 'contact', 'status', 'custody', 'attachment', 'image_file_id',
        'posted_at', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'title', 'description', 'location', 'reported_by', 'category'];

    /** @return array{total:int,lost:int,found:int,open:int,claim_pending:int,returned:int,unclaimed:int,archived:int} */
    public static function counts(): array
    {
        $row = DB::table('lost_found_items')
            ->selectRaw('COUNT(*) AS total,
                    SUM(type = "Lost") AS lost,
                    SUM(type = "Found") AS found,
                    SUM(status IN ("Open", "Unclaimed")) AS open,
                    SUM(status = "Claim Pending") AS claim_pending,
                    SUM(status = "Returned") AS returned,
                    SUM(status = "Unclaimed") AS unclaimed,
                    SUM(status = "Archived") AS archived')
            ->first();

        return array_map('intval', $row === null ? [] : (array) $row);
    }

    /** @return array<int, int> item ID => pending claim count */
    public static function pendingClaimCounts(): array
    {
        $rows = DB::table('lost_found_claims')
            ->where('status', 'Pending')
            ->select('item_id')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('item_id')
            ->get();
        $counts = [];

        foreach ($rows as $row) {
            $row = (array) $row;
            $counts[(int) $row['item_id']] = (int) $row['total'];
        }

        return $counts;
    }

    /** @return list<array<string, mixed>> */
    public static function visible(): array
    {
        return DB::table('lost_found_items')
            ->where('status', '<>', 'Archived')
            ->orderByRaw('CASE WHEN status = "Claim Pending" THEN 0 WHEN status IN ("Open", "Unclaimed") THEN 1 ELSE 2 END,
                      posted_at DESC, id DESC')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function findVisible(string $publicId): ?array
    {
        $row = DB::table('lost_found_items')
            ->where('public_id', $publicId)
            ->where('status', '<>', 'Archived')
            ->first();

        return $row === null ? null : (array) $row;
    }

    /** @return list<array<string, mixed>> */
    public static function claimsFor(int $itemId): array
    {
        return DB::table('lost_found_claims')
            ->where('item_id', $itemId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function forReporter(string $email, string $studentId = ''): array
    {
        return DB::table('lost_found_items')
            ->where(static function ($group) use ($email, $studentId): void {
                $group->where('reporter_email', $email)
                    ->orWhere(static function ($inner) use ($studentId): void {
                        $inner->where('student_id', '<>', '')
                            ->where('student_id', $studentId);
                    });
            })
            ->orderBy('posted_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function claimsByUser(int $userId): array
    {
        return DB::table('lost_found_claims as c')
            ->join('lost_found_items as i', 'i.id', '=', 'c.item_id')
            ->where('c.claimant_user_id', $userId)
            ->select(
                'c.*',
                'i.public_id as item_code',
                'i.title as item_title',
                'i.type as item_type',
                'i.status as item_status',
            )
            ->orderBy('c.created_at', 'desc')
            ->orderBy('c.id', 'desc')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function pendingClaims(int $limit = 20): array
    {
        return DB::table('lost_found_claims as c')
            ->join('lost_found_items as i', 'i.id', '=', 'c.item_id')
            ->where('c.status', 'Pending')
            ->select('c.*', 'i.public_id as item_code', 'i.title as item_title')
            ->orderBy('c.created_at')
            ->limit(max(1, min(100, $limit)))
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }
}
