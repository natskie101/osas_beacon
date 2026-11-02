<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DB;

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
        $where = '';
        $bindings = [];

        if ($from !== null && $from !== '') {
            $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'DATE({date_column}) >= ?';
            $bindings[] = $from;
        }

        if ($to !== null && $to !== '') {
            $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'DATE({date_column}) <= ?';
            $bindings[] = $to;
        }

        [$columns, $sql, $dateColumn] = self::query($type);
        $where = str_replace('{date_column}', $dateColumn, $where);

        $rows = DB::select($sql . $where . ' ORDER BY ' . $dateColumn . ' DESC', $bindings);

        return [
            'title' => $definition['label'] . ' Report',
            'category' => $definition['category'],
            'columns' => $columns,
            'rows' => $rows,
            'record_count' => count($rows),
        ];
    }

    /**
     * @return array{0:list<array{key:string,label:string}>,1:string,2:string}
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
                'SELECT public_id, CONCAT(first_name, " ", last_name) AS full_name, email, role, student_id, program, year_level, status, created_at FROM users',
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
                'SELECT public_id, title, category, audience, status, priority, views, published_at FROM announcements',
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
                'SELECT public_id, reference_code, title, category, status, effective_date, views, updated_at FROM regulations',
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
                'SELECT public_id, title, category, status, mandatory, duration_minutes, learners, completions, completion_rate FROM orientation_modules',
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
                'SELECT public_id, title, category, status, start_at, venue, mode, registered, capacity FROM events',
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
                'SELECT public_id, type, title, category, location, status, reported_by, posted_at FROM lost_found_items',
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
                'SELECT public_id, student_name, student_id, document_type, status, priority, requested_at, released_at, processed_by FROM document_requests',
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
                'SELECT public_id, student_name, category, subject, priority, status, assigned_to, rating, submitted_at FROM support_tickets',
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
                'SELECT public_id, subject, channel, audience, status, reach, delivered, opened, sent_at FROM broadcasts',
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
                'SELECT public_id, title, type, views, unique_visitors, avg_time, engagement, last_viewed FROM tracking',
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
                'SELECT created_at, actor_name, action, title, text, ip_address FROM audit_log',
                'created_at',
            ],
        };
    }
}
