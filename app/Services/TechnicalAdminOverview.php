<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Participation;
use App\Models\SupportTicket;
use Illuminate\Database\Query\Builder;
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
     *     top_orientations:list<array{public_id:string,title:string,learners:int,completions:int}>,
     *     orientation_compliance:array{total:int,compliant:int,in_progress:int,at_risk:int,rate:float,hours:int,events:int,mandatory_modules:int},
     *     attention:list<array{key:string,label:string,count:int,oldest_days:int,url:string}>
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
        // Same numbers the Analytics page reports, so the two pages cannot disagree.
        $orientationCompliance = Participation::summary(Participation::students());
        $orientationCompliance['mandatory_modules'] = Participation::mandatoryModuleCount();
        $attention = self::attentionQueue();

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
            // Verified students only: Pending accounts are still awaiting OSAS
            // verification and must not appear in the program breakdown.
            'student_programs' => DB::table('users')
                ->selectRaw("COALESCE(NULLIF(TRIM(program), ''), 'Unassigned') AS program, COUNT(*) AS count")
                ->where('role', 'Student')
                ->where('status', 'Active')
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
            'orientation_compliance' => $orientationCompliance,
            'attention' => $attention,
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

    /**
     * Queues where work is waiting for OSAS, each carrying the age of its oldest item.
     *
     * Only queues whose admin index the Administrator role may open are listed:
     * document requests and lost & found sit behind role:Coordinator, so linking
     * them from an Administrator-only page would bounce the user to /login.
     *
     * Counts and ages come from the same filtered query, so the number shown and the
     * waiting time always describe the same set of rows.
     *
     * @return list<array{key:string,label:string,count:int,oldest_days:int,url:string}>
     */
    private static function attentionQueue(): array
    {
        return [
            self::queueEntry(
                'pending_accounts',
                'Pending accounts',
                DB::table('users')->where('status', 'Pending'),
                '/admin/users?tab=queue',
            ),
            self::queueEntry(
                'support_open',
                'Open support tickets',
                DB::table('support_tickets')->where('status', 'Open'),
                '/admin/support?status=Open',
            ),
        ];
    }

    /**
     * Count one queue and work out how long its oldest row has been waiting.
     *
     * @param Builder $query The queue's filters; aggregates run on clones, so it stays reusable.
     * @param string  $url   The admin index that shows these rows.
     * @return array{key:string,label:string,count:int,oldest_days:int,url:string}
     */
    private static function queueEntry(string $key, string $label, Builder $query, string $url): array
    {
        $count = (int) $query->count();
        $oldestDays = 0;

        if ($count > 0) {
            $oldest = $query->min('created_at');
            $timestamp = is_string($oldest) ? strtotime($oldest) : false;

            if ($timestamp !== false) {
                $oldestDays = max(0, (int) floor((time() - $timestamp) / 86400));
            }
        }

        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'oldest_days' => $oldestDays,
            'url' => $url,
        ];
    }
}
