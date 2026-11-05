<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class DocumentRequest extends BaseModel
{
    protected static string $table = 'document_requests';
    protected static string $prefix = 'DR';
    protected static array $fillable = [
        'student_user_id', 'student_name', 'student_id', 'student_email', 'program', 'year_level',
        'document_type', 'purpose', 'copies', 'delivery_mode', 'status', 'priority', 'requested_at',
        'released_at', 'processed_by', 'remarks', 'attachment',
    ];
    protected static array $searchable = ['public_id', 'student_name', 'student_id', 'student_email', 'document_type', 'purpose'];

    /** @return array{total:int,submitted:int,review:int,ready:int,released:int,rejected:int,cancelled:int} */
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Submitted") AS submitted,
                    SUM(status = "Under Review") AS review,
                    SUM(status = "Ready for Pick-up") AS ready,
                    SUM(status = "Released") AS released,
                    SUM(status = "Rejected") AS rejected,
                    SUM(status = "Cancelled") AS cancelled
             FROM document_requests',
        );

        return array_map('intval', $row ?? []);
    }

    /** @return list<array<string, mixed>> */
    public static function forStudent(int $userId): array
    {
        return DB::select(
            'SELECT * FROM document_requests WHERE student_user_id = ? ORDER BY requested_at DESC, id DESC',
            [$userId],
        );
    }

    /** @return array<string, mixed>|null */
    public static function findForStudent(string $publicId, int $userId): ?array
    {
        return DB::first(
            'SELECT * FROM document_requests WHERE public_id = ? AND student_user_id = ? LIMIT 1',
            [$publicId, $userId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function timeline(int $requestId): array
    {
        return DB::select(
            'SELECT * FROM document_request_events WHERE request_id = ? ORDER BY created_at ASC, id ASC',
            [$requestId],
        );
    }

    public static function addEvent(int $requestId, string $status, string $byName, string $note = ''): void
    {
        DB::insert(
            'INSERT INTO document_request_events (request_id, status, by_name, note, created_at) VALUES (?, ?, ?, ?, ?)',
            [$requestId, $status, $byName, $note, date('Y-m-d H:i:s')],
        );
    }

    public static function statuses(): array
    {
        return ['Submitted', 'Under Review', 'Ready for Pick-up', 'Released', 'Rejected', 'Cancelled'];
    }

    /** @return array{document_types:list<string>,delivery_modes:list<string>} */
    public static function filterOptions(): array
    {
        $documentTypes = DB::select(
            'SELECT DISTINCT document_type FROM document_requests WHERE document_type <> "" ORDER BY document_type',
        );
        $deliveryModes = DB::select(
            'SELECT DISTINCT delivery_mode FROM document_requests WHERE delivery_mode <> "" ORDER BY delivery_mode',
        );

        return [
            'document_types' => array_values(array_map(
                static fn (array $row): string => (string) $row['document_type'],
                $documentTypes,
            )),
            'delivery_modes' => array_values(array_map(
                static fn (array $row): string => (string) $row['delivery_mode'],
                $deliveryModes,
            )),
        ];
    }
}
