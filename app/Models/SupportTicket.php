<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

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
        $row = DB::table('support_tickets')
            ->selectRaw('COUNT(*) AS total,
                    SUM(status = "Open") AS open,
                    SUM(status = "In Progress") AS in_progress,
                    SUM(status = "Resolved") AS resolved,
                    SUM(status = "Closed") AS closed,
                    COALESCE(AVG(rating), 0) AS avg_rating')
            ->first();
        $row = $row === null ? [] : (array) $row;

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
        return DB::table('support_tickets')
            ->where('student_user_id', $userId)
            ->orderBy('submitted_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function findForStudent(string $publicId, int $userId): ?array
    {
        $row = DB::table('support_tickets')
            ->where('public_id', $publicId)
            ->where('student_user_id', $userId)
            ->first();

        return $row === null ? null : (array) $row;
    }

    /** @return array<string, mixed>|null */
    public static function imageAttachment(string $publicId): ?array
    {
        $file = DB::table('files')
            ->where('entity_type', 'support_ticket')
            ->where('entity_id', $publicId)
            ->whereIn('mime_type', ['image/jpeg', 'image/png'])
            ->orderBy('id', 'desc')
            ->first();

        return $file === null ? null : (array) $file;
    }

    /** @return list<array<string, mixed>> */
    public static function messages(int $ticketId): array
    {
        return DB::table('support_messages')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public static function addMessage(int $ticketId, string $authorName, string $role, string $body): void
    {
        DB::table('support_messages')->insert([
            'ticket_id' => $ticketId,
            'author_name' => $authorName,
            'role' => $role,
            'body' => $body,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
