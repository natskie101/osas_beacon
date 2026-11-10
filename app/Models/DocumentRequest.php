<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

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
        $row = DB::table('document_requests')
            ->selectRaw('COUNT(*) AS total,
                    SUM(status = "Submitted") AS submitted,
                    SUM(status = "Under Review") AS review,
                    SUM(status = "Ready for Pick-up") AS ready,
                    SUM(status = "Released") AS released,
                    SUM(status = "Rejected") AS rejected,
                    SUM(status = "Cancelled") AS cancelled')
            ->first();

        return array_map('intval', $row === null ? [] : (array) $row);
    }

    /** @return list<array<string, mixed>> */
    public static function forStudent(int $userId): array
    {
        return DB::table('document_requests')
            ->where('student_user_id', $userId)
            ->orderBy('requested_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function findForStudent(string $publicId, int $userId): ?array
    {
        $row = DB::table('document_requests')
            ->where('public_id', $publicId)
            ->where('student_user_id', $userId)
            ->first();

        return $row === null ? null : (array) $row;
    }

    /** @return list<array<string, mixed>> */
    public static function timeline(int $requestId): array
    {
        return DB::table('document_request_events')
            ->where('request_id', $requestId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public static function addEvent(int $requestId, string $status, string $byName, string $note = ''): void
    {
        DB::table('document_request_events')->insert([
            'request_id' => $requestId,
            'status' => $status,
            'by_name' => $byName,
            'note' => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function statuses(): array
    {
        return ['Submitted', 'Under Review', 'Ready for Pick-up', 'Released', 'Rejected', 'Cancelled'];
    }

    /** @return array{document_types:list<string>,delivery_modes:list<string>} */
    public static function filterOptions(): array
    {
        $documentTypes = DB::table('document_requests')
            ->where('document_type', '<>', '')
            ->distinct()
            ->orderBy('document_type')
            ->pluck('document_type')
            ->all();
        $deliveryModes = DB::table('document_requests')
            ->where('delivery_mode', '<>', '')
            ->distinct()
            ->orderBy('delivery_mode')
            ->pluck('delivery_mode')
            ->all();

        return [
            'document_types' => array_values(array_map(
                static fn (string $value): string => (string) $value,
                $documentTypes,
            )),
            'delivery_modes' => array_values(array_map(
                static fn (string $value): string => (string) $value,
                $deliveryModes,
            )),
        ];
    }
}
