<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class ReportService
{
    /**
     * @return array<string, array{label:string,category:string,description:string}>
     */
    public static function types(): array
    {
        return [
            'users' => ['label' => 'User Account & Access Management', 'category' => 'User Management', 'description' => 'Every portal account, role, program and status.'],
            'announcements' => ['label' => 'Announcement & Information Broadcast', 'category' => 'Announcements', 'description' => 'Announcement publishing status and portal views.'],
            'regulations' => ['label' => 'Student Code of Conduct & Regulations', 'category' => 'Rules & Regulations', 'description' => 'Policy library, effective dates and review state.'],
            'orientation' => ['label' => 'Student Orientation & Information Modules', 'category' => 'Orientation Content', 'description' => 'Module completion and learner reach.'],
            'events' => ['label' => 'Events & Activities Participation', 'category' => 'Events & Activities', 'description' => 'Event schedule, venue and recorded attendance.'],
            'lost_found' => ['label' => 'Lost & Found Records', 'category' => 'Lost & Found', 'description' => 'Lost and found items, custody and claim status.'],
            'document_requests' => ['label' => 'Document Request Processing', 'category' => 'Document Requests', 'description' => 'Requests, processing state and release dates.'],
            'support' => ['label' => 'Feedback & Support Center', 'category' => 'Feedback & Support', 'description' => 'Support tickets, priorities and satisfaction ratings.'],
            'broadcasts' => ['label' => 'Broadcast Delivery', 'category' => 'Broadcasts', 'description' => 'Audience reach and delivery statistics.'],
            'tracking' => ['label' => 'Viewing & Tracking Analytics', 'category' => 'Viewing & Tracking', 'description' => 'Content views, unique visitors and engagement.'],
            'activity' => ['label' => 'User Activity & Audit Log', 'category' => 'User Activity', 'description' => 'Server-recorded account and content actions.'],
        ];
    }

    /**
     * @return array{title:string,category:string,columns:list<array{key:string,label:string}>,rows:list<array<string,mixed>>,record_count:int}
     */
    public static function dataset(string $type, ?string $from = null, ?string $to = null): array
    {
        $types = self::types();
        $definition = $types[$type] ?? $types['announcements'];
        [$columns, $table, $selects, $dateColumn] = self::query($type);

        $query = DB::table($table)->select($selects);

        if ($from !== null && $from !== '') {
            $query->whereRaw('DATE(' . $dateColumn . ') >= ?', [$from]);
        }

        if ($to !== null && $to !== '') {
            $query->whereRaw('DATE(' . $dateColumn . ') <= ?', [$to]);
        }

        $rows = $query->orderBy($dateColumn, 'desc')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();

        return [
            'title' => $definition['label'] . ' Report',
            'category' => $definition['category'],
            'columns' => $columns,
            'rows' => $rows,
            'record_count' => count($rows),
        ];
    }

    /**
     * @return array{0:list<array{key:string,label:string}>,1:string,2:list<string|\Illuminate\Contracts\Database\Query\Expression>,3:string}
     */
    private static function query(string $type): array
    {
        return match ($type) {
            'users' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'full_name', 'label' => 'Name'],
                    ['key' => 'email', 'label' => 'Email'],
                    ['key' => 'role', 'label' => 'Role'],
                    ['key' => 'student_id', 'label' => 'Student ID'],
                    ['key' => 'program', 'label' => 'Program'],
                    ['key' => 'year_level', 'label' => 'Year Level'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'created_at', 'label' => 'Created'],
                ],
                'users',
                ['public_id', DB::raw("CONCAT(first_name, ' ', last_name) AS full_name"), 'email', 'role', 'student_id', 'program', 'year_level', 'status', 'created_at'],
                'created_at',
            ],
            'announcements' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'category', 'label' => 'Category'],
                    ['key' => 'audience', 'label' => 'Audience'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'priority', 'label' => 'Priority'],
                    ['key' => 'views', 'label' => 'Views'],
                    ['key' => 'published_at', 'label' => 'Published'],
                ],
                'announcements',
                ['public_id', 'title', 'category', 'audience', 'status', 'priority', 'views', 'published_at'],
                'created_at',
            ],
            'regulations' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'reference_code', 'label' => 'Code'],
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'category', 'label' => 'Category'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'effective_date', 'label' => 'Effective Date'],
                    ['key' => 'views', 'label' => 'Views'],
                    ['key' => 'updated_at', 'label' => 'Updated'],
                ],
                'regulations',
                ['public_id', 'reference_code', 'title', 'category', 'status', 'effective_date', 'views', 'updated_at'],
                'updated_at',
            ],
            'orientation' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'title', 'label' => 'Module'],
                    ['key' => 'category', 'label' => 'Category'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'mandatory', 'label' => 'Mandatory'],
                    ['key' => 'duration_minutes', 'label' => 'Minutes'],
                    ['key' => 'learners', 'label' => 'Learners'],
                    ['key' => 'completions', 'label' => 'Completions'],
                    ['key' => 'completion_rate', 'label' => 'Completion %'],
                ],
                'orientation_modules',
                ['public_id', 'title', 'category', 'status', 'mandatory', 'duration_minutes', 'learners', 'completions', 'completion_rate'],
                'updated_at',
            ],
            'events' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'title', 'label' => 'Event'],
                    ['key' => 'category', 'label' => 'Category'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'start_at', 'label' => 'Starts'],
                    ['key' => 'venue', 'label' => 'Venue'],
                    ['key' => 'mode', 'label' => 'Mode'],
                    ['key' => 'registered', 'label' => 'Registered'],
                    ['key' => 'capacity', 'label' => 'Capacity'],
                ],
                'events',
                ['public_id', 'title', 'category', 'status', 'start_at', 'venue', 'mode', 'registered', 'capacity'],
                'start_at',
            ],
            'lost_found' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'type', 'label' => 'Type'],
                    ['key' => 'title', 'label' => 'Item'],
                    ['key' => 'category', 'label' => 'Category'],
                    ['key' => 'location', 'label' => 'Location'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'reported_by', 'label' => 'Reported By'],
                    ['key' => 'posted_at', 'label' => 'Posted'],
                ],
                'lost_found_items',
                ['public_id', 'type', 'title', 'category', 'location', 'status', 'reported_by', 'posted_at'],
                'posted_at',
            ],
            'document_requests' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'student_name', 'label' => 'Student'],
                    ['key' => 'student_id', 'label' => 'Student ID'],
                    ['key' => 'document_type', 'label' => 'Document'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'priority', 'label' => 'Priority'],
                    ['key' => 'requested_at', 'label' => 'Requested'],
                    ['key' => 'released_at', 'label' => 'Released'],
                    ['key' => 'processed_by', 'label' => 'Processed By'],
                ],
                'document_requests',
                ['public_id', 'student_name', 'student_id', 'document_type', 'status', 'priority', 'requested_at', 'released_at', 'processed_by'],
                'requested_at',
            ],
            'support' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'student_name', 'label' => 'Student'],
                    ['key' => 'category', 'label' => 'Category'],
                    ['key' => 'subject', 'label' => 'Subject'],
                    ['key' => 'priority', 'label' => 'Priority'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'assigned_to', 'label' => 'Assigned To'],
                    ['key' => 'rating', 'label' => 'Rating'],
                    ['key' => 'submitted_at', 'label' => 'Submitted'],
                ],
                'support_tickets',
                ['public_id', 'student_name', 'category', 'subject', 'priority', 'status', 'assigned_to', 'rating', 'submitted_at'],
                'submitted_at',
            ],
            'broadcasts' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'subject', 'label' => 'Subject'],
                    ['key' => 'channel', 'label' => 'Channel'],
                    ['key' => 'audience', 'label' => 'Audience'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'reach', 'label' => 'Reach'],
                    ['key' => 'delivered', 'label' => 'Delivered'],
                    ['key' => 'opened', 'label' => 'Opened'],
                    ['key' => 'sent_at', 'label' => 'Sent'],
                ],
                'broadcasts',
                ['public_id', 'subject', 'channel', 'audience', 'status', 'reach', 'delivered', 'opened', 'sent_at'],
                'created_at',
            ],
            'tracking' => [
                [
                    ['key' => 'public_id', 'label' => 'Reference'],
                    ['key' => 'title', 'label' => 'Content'],
                    ['key' => 'type', 'label' => 'Type'],
                    ['key' => 'views', 'label' => 'Views'],
                    ['key' => 'unique_visitors', 'label' => 'Unique Visitors'],
                    ['key' => 'avg_time', 'label' => 'Average Time'],
                    ['key' => 'engagement', 'label' => 'Engagement'],
                    ['key' => 'last_viewed', 'label' => 'Last Viewed'],
                ],
                'tracking',
                ['public_id', 'title', 'type', 'views', 'unique_visitors', 'avg_time', 'engagement', 'last_viewed'],
                'last_viewed',
            ],
            default => [
                [
                    ['key' => 'created_at', 'label' => 'Timestamp'],
                    ['key' => 'actor_name', 'label' => 'Actor'],
                    ['key' => 'action', 'label' => 'Action'],
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'text', 'label' => 'Details'],
                    ['key' => 'ip_address', 'label' => 'IP Address'],
                ],
                'audit_log',
                ['created_at', 'actor_name', 'action', 'title', 'text', 'ip_address'],
                'created_at',
            ],
        };
    }
}
