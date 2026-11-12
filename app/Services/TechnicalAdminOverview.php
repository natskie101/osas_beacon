<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

final class TechnicalAdminOverview
{
    /**
     * @return array{
     *     active_today:int,
     *     failed_logins_24h:int,
     *     activity_trend:list<array{date:string,label:string,logins:int,failed:int}>,
     *     account_statuses:list<array{status:string,count:int}>,
     *     student_programs:list<array{program:string,count:int}>,
     *     operational_queue:array{
     *         support_open:int,support_in_progress:int,support_resolved:int,support_closed:int,
     *         documents:array<string,int>,lost_found:array<string,int>,events:array<string,int>,announcements:array<string,int>
     *     },
     *     health_checks:list<array{label:string,ok:bool,detail:string}>,
     *     top_announcements:list<array{public_id:string,title:string,views:int}>,
     *     top_orientations:list<array{public_id:string,title:string,learners:int,completions:int}>
     * }
     */
    public static function data(): array
    {
        $today = date('Y-m-d 00:00:00');
        $tomorrow = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $trendStart = date('Y-m-d 00:00:00', strtotime('-6 days'));
        $since = date('Y-m-d H:i:s', time() - 86400);
        $activityByDate = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $date = date('Y-m-d', strtotime('-' . $offset . ' days'));
            $activityByDate[$date] = [
                'date' => $date,
                'label' => date('D', strtotime($date)),
                'logins' => 0,
                'failed' => 0,
            ];
        }

        $loginTrend = DB::table('audit_log')
            ->selectRaw('DATE(created_at) AS activity_date, COUNT(DISTINCT actor_user_id) AS total')
            ->where('action', 'auth.login')
            ->whereNotNull('actor_user_id')
            ->where('created_at', '>=', $trendStart)
            ->where('created_at', '<', $tomorrow)
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
        foreach ($loginTrend as $row) {
            $date = (string) $row['activity_date'];
            if (isset($activityByDate[$date])) {
                $activityByDate[$date]['logins'] = (int) $row['total'];
            }
        }

        $failedTrend = DB::table('login_attempts')
            ->selectRaw('DATE(attempted_at) AS activity_date, COUNT(*) AS total')
            ->where('successful', 0)
            ->where('attempted_at', '>=', $trendStart)
            ->where('attempted_at', '<', $tomorrow)
            ->groupByRaw('DATE(attempted_at)')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
        foreach ($failedTrend as $row) {
            $date = (string) $row['activity_date'];
            if (isset($activityByDate[$date])) {
                $activityByDate[$date]['failed'] = (int) $row['total'];
            }
        }

        $missingExtensions = array_values(array_filter(
            ['pdo_pgsql', 'mbstring', 'fileinfo', 'json', 'openssl'],
            static fn (string $extension): bool => !extension_loaded($extension),
        ));
        $storagePaths = [
            'Logs' => BASE_PATH . '/storage/logs',
            'Sessions' => BASE_PATH . '/storage/framework/sessions',
            'Uploads' => BASE_PATH . '/storage/uploads',
        ];
        $unwritablePaths = array_keys(array_filter(
            $storagePaths,
            static fn (string $path): bool => !is_dir($path) || !is_writable($path),
        ));
        $supportCounts = SupportTicket::counts();
        $documentStatuses = self::statusCounts(
            'document_requests',
            ['Submitted', 'Under Review', 'Ready for Pick-up', 'Released', 'Rejected', 'Cancelled'],
        );
        $lostFoundStatuses = self::statusCounts(
            'lost_found_items',
            ['Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed'],
        );
        $eventStatuses = self::statusCounts('events', ['Scheduled', 'Ongoing', 'Completed', 'Cancelled']);
        $announcementStatuses = self::statusCounts(
            'announcements',
            ['Draft', 'Scheduled', 'Published', 'Archived'],
        );

        return [
            'active_today' => DB::table('audit_log')
                ->where('action', 'auth.login')
                ->whereNotNull('actor_user_id')
                ->where('created_at', '>=', $today)
                ->where('created_at', '<', $tomorrow)
                ->distinct()
                ->count('actor_user_id'),
            'failed_logins_24h' => DB::table('login_attempts')
                ->where('successful', 0)
                ->where('attempted_at', '>=', $since)
                ->count(),
            'activity_trend' => array_values($activityByDate),
            'account_statuses' => DB::table('users')
                ->selectRaw('status, COUNT(*) AS count')
                ->groupBy('status')
                ->orderByRaw("CASE status WHEN 'Active' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Inactive' THEN 3 ELSE 4 END")
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
            'student_programs' => DB::table('users')
                ->selectRaw("COALESCE(NULLIF(TRIM(program), ''), 'Unassigned') AS program, COUNT(*) AS count")
                ->where('role', 'Student')
                ->groupByRaw("COALESCE(NULLIF(TRIM(program), ''), 'Unassigned')")
                ->orderByRaw('count DESC, program ASC')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
            'operational_queue' => [
                'support_open' => $supportCounts['open'],
                'support_in_progress' => $supportCounts['in_progress'],
                'support_resolved' => $supportCounts['resolved'],
                'support_closed' => $supportCounts['closed'],
                'documents' => $documentStatuses,
                'lost_found' => $lostFoundStatuses,
                'events' => $eventStatuses,
                'announcements' => $announcementStatuses,
            ],
            'health_checks' => [
                [
                    'label' => 'Database',
                    'ok' => DB::scalar('SELECT 1') !== null,
                    'detail' => 'Connection and query check passed.',
                ],
                [
                    'label' => 'PHP version',
                    'ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
                    'detail' => PHP_VERSION,
                ],
                [
                    'label' => 'Required PHP extensions',
                    'ok' => $missingExtensions === [],
                    'detail' => $missingExtensions === []
                        ? 'All required extensions are loaded.'
                        : 'Missing: ' . implode(', ', $missingExtensions),
                ],
                [
                    'label' => 'Storage permissions',
                    'ok' => $unwritablePaths === [],
                    'detail' => $unwritablePaths === []
                        ? 'Logs, sessions, and uploads are writable.'
                        : 'Check: ' . implode(', ', $unwritablePaths),
                ],
            ],
            'top_announcements' => DB::table('announcements')
                ->select('public_id', 'title', 'views')
                ->where('status', 'Published')
                ->orderByDesc('views')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
            'top_orientations' => DB::table('orientation_modules')
                ->select('public_id', 'title', 'learners', 'completions')
                ->where('status', 'Published')
                ->orderByDesc('learners')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
        ];
    }

    /**
     * @param list<string> $statuses
     * @return array<string, int>
     */
    private static function statusCounts(string $table, array $statuses): array
    {
        $counts = array_fill_keys($statuses, 0);

        $rows = DB::table($table)
            ->selectRaw('status, COUNT(*) AS count')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $status = (string) $row['status'];
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['count'];
            }
        }

        return $counts;
    }
}
