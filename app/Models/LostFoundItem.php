<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class LostFoundItem extends BaseModel
{
    protected static string $table = 'lost_found_items';
    protected static string $prefix = 'LF';
    protected static array $fillable = [
        'type', 'title', 'category', 'description', 'location', 'date_occurred', 'reported_by',
        'reporter_email', 'student_id', 'contact', 'status', 'custody', 'attachment', 'posted_at', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'title', 'description', 'location', 'reported_by', 'category'];

    /** @return array{total:int,lost:int,found:int,open:int,claim_pending:int,returned:int,archived:int} */
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(type = "Lost") AS lost,
                    SUM(type = "Found") AS found,
                    SUM(status IN ("Open", "Unclaimed")) AS open,
                    SUM(status = "Claim Pending") AS claim_pending,
                    SUM(status = "Returned") AS returned,
                    SUM(status = "Archived") AS archived
             FROM lost_found_items',
        );

        return array_map('intval', $row ?? []);
    }

    /** @return list<array<string, mixed>> */
    public static function visible(): array
    {
        return DB::select(
            'SELECT * FROM lost_found_items
             WHERE status <> "Archived"
             ORDER BY CASE WHEN status = "Claim Pending" THEN 0 WHEN status IN ("Open", "Unclaimed") THEN 1 ELSE 2 END,
                      posted_at DESC, id DESC',
        );
    }

    /** @return array<string, mixed>|null */
    public static function findVisible(string $publicId): ?array
    {
        return DB::first(
            'SELECT * FROM lost_found_items WHERE public_id = ? AND status <> "Archived" LIMIT 1',
            [$publicId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function claimsFor(int $itemId): array
    {
        return DB::select(
            'SELECT * FROM lost_found_claims WHERE item_id = ? ORDER BY created_at DESC, id DESC',
            [$itemId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function forReporter(string $email, string $studentId = ''): array
    {
        return DB::select(
            'SELECT * FROM lost_found_items
             WHERE reporter_email = ? OR (student_id <> "" AND student_id = ?)
             ORDER BY posted_at DESC, id DESC',
            [$email, $studentId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function claimsByUser(int $userId): array
    {
        return DB::select(
            'SELECT c.*, i.public_id AS item_code, i.title AS item_title, i.type AS item_type, i.status AS item_status
             FROM lost_found_claims c
             JOIN lost_found_items i ON i.id = c.item_id
             WHERE c.claimant_user_id = ?
             ORDER BY c.created_at DESC, c.id DESC',
            [$userId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function pendingClaims(int $limit = 20): array
    {
        return DB::select(
            'SELECT c.*, i.public_id AS item_code, i.title AS item_title
             FROM lost_found_claims c
             JOIN lost_found_items i ON i.id = c.item_id
             WHERE c.status = "Pending"
             ORDER BY c.created_at ASC
             LIMIT ' . max(1, min(100, $limit)),
        );
    }
}
