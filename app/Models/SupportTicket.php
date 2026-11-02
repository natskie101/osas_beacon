<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class SupportTicket extends BaseModel
{
    protected static string $table = 'support_tickets';
    protected static string $prefix = 'TCS';
    protected static array $fillable = [
        'student_user_id', 'student_name', 'student_email', 'student_id', 'program', 'category',
        'subject', 'description', 'priority', 'status', 'assigned_to', 'rating', 'submitted_at',
    ];
    protected static array $searchable = ['public_id', 'student_name', 'student_email', 'subject', 'description', 'category'];

    /** @return array{total:int,open:int,in_progress:int,resolved:int,closed:int,avg_rating:float} */
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Open") AS open,
                    SUM(status = "In Progress") AS in_progress,
                    SUM(status = "Resolved") AS resolved,
                    SUM(status = "Closed") AS closed,
                    COALESCE(AVG(rating), 0) AS avg_rating
             FROM support_tickets',
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'open' => (int) ($row['open'] ?? 0),
            'in_progress' => (int) ($row['in_progress'] ?? 0),
            'resolved' => (int) ($row['resolved'] ?? 0),
            'closed' => (int) ($row['closed'] ?? 0),
            'avg_rating' => (float) ($row['avg_rating'] ?? 0),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function forStudent(int $userId): array
    {
        return DB::select(
            'SELECT * FROM support_tickets WHERE student_user_id = ? ORDER BY submitted_at DESC, id DESC',
            [$userId],
        );
    }

    /** @return array<string, mixed>|null */
    public static function findForStudent(string $publicId, int $userId): ?array
    {
        return DB::first(
            'SELECT * FROM support_tickets WHERE public_id = ? AND student_user_id = ? LIMIT 1',
            [$publicId, $userId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function messages(int $ticketId): array
    {
        return DB::select(
            'SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC, id ASC',
            [$ticketId],
        );
    }

    public static function addMessage(int $ticketId, string $authorName, string $role, string $body): void
    {
        DB::insert(
            'INSERT INTO support_messages (ticket_id, author_name, role, body, created_at) VALUES (?, ?, ?, ?, ?)',
            [$ticketId, $authorName, $role, $body, date('Y-m-d H:i:s')],
        );
    }
}
