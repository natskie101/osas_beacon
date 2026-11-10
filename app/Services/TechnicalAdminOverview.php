<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SupportTicket;
use App\Support\DB;

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

        $loginTrend = DB::select(
            'SELECT DATE(created_at) AS activity_date, COUNT(DISTINCT actor_user_id) AS total
             FROM audit_log
             WHERE action = ? AND actor_user_id IS NOT NULL AND created_at >= ? AND created_at < ?
             GROUP BY DATE(created_at)',
            ['auth.login', $trendStart, $tomorrow],
        );
        foreach ($loginTrend as $row) {
            $date = (string) $row['activity_date'];
            if (isset($activityByDate[$date])) {
                $activityByDate[$date]['logins'] = (int) $row['total'];
            }
        }

        $failedTrend = DB::select(
            'SELECT DATE(attempted_at) AS activity_date, COUNT(*) AS total
             FROM login_attempts
             WHERE successful = 0 AND attempted_at >= ? AND attempted_at < ?
             GROUP BY DATE(attempted_at)',
            [$trendStart, $tomorrow],
        );
        foreach ($failedTrend as $row) {
            $date = (string) $row['activity_date'];
            if (isset($activityByDate[$date])) {
                $activityByDate[$date]['failed'] = (int) $row['total'];
            }
        }

        $missingExtensions = array_values(array_filter(
            ['pdo_mysql', 'mbstring', 'fileinfo', 'json', 'openssl'],
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
            'active_today' => (int) DB::value(
                'SELECT COUNT(DISTINCT actor_user_id)
                 FROM audit_log
                 WHERE action = ? AND actor_user_id IS NOT NULL
                   AND created_at >= ? AND created_at < ?',
                ['auth.login', $today, $tomorrow],
            ),
            'failed_logins_24h' => (int) DB::value(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE successful = 0 AND attempted_at >= ?',
                [$since],
            ),
            'activity_trend' => array_values($activityByDate),
            'account_statuses' => DB::select(
                'SELECT status, COUNT(*) AS count
                 FROM users
                 GROUP BY status
                 ORDER BY CASE status
                     WHEN "Active" THEN 1
                     WHEN "Pending" THEN 2
                     WHEN "Inactive" THEN 3
                     ELSE 4
                 END',
            ),
            'student_programs' => DB::select(
                'SELECT COALESCE(NULLIF(TRIM(program), ""), "Unassigned") AS program, COUNT(*) AS count
                 FROM users
                 WHERE role = ?
                 GROUP BY COALESCE(NULLIF(TRIM(program), ""), "Unassigned")
                 ORDER BY count DESC, program ASC',
                ['Student'],
            ),
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
                    'ok' => DB::value('SELECT 1') !== null,
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
            'top_announcements' => DB::select(
                'SELECT public_id, title, views
                 FROM announcements
                 WHERE status = ?
                 ORDER BY views DESC, id DESC
                 LIMIT 5',
                ['Published'],
            ),
            'top_orientations' => DB::select(
                'SELECT public_id, title, learners, completions
                 FROM orientation_modules
                 WHERE status = ?
                 ORDER BY learners DESC, id DESC
                 LIMIT 5',
                ['Published'],
            ),
        ];
    }

    /**
     * @param list<string> $statuses
     * @return array<string, int>
     */
    private static function statusCounts(string $table, array $statuses): array
    {
        $counts = array_fill_keys($statuses, 0);

        foreach (DB::select('SELECT status, COUNT(*) AS count FROM ' . $table . ' GROUP BY status') as $row) {
            $status = (string) $row['status'];
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['count'];
            }
        }

        return $counts;
    }
}
