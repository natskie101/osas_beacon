<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

final class DatabaseSeeder
{
    public function run(bool $fresh = false, bool $minimal = false): void
    {
        $path = BASE_PATH . '/database/data/seed.json';

        if (!is_file($path)) {
            throw new \RuntimeException('Seed data not found at database/data/seed.json');
        }

        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $minimalPasswords = [];

        if ($minimal) {
            $requiredAccounts = [
                'josefina.reyes@uc.edu.ph' => ['Coordinator', 'SEED_COORDINATOR_PASSWORD'],
                'ryan.delacruz@uc.edu.ph' => ['Student', 'SEED_STUDENT_PASSWORD'],
                'technical.admin@uc.edu.ph' => ['Administrator', 'SEED_ADMIN_PASSWORD'],
            ];
            $usersByEmail = [];
            foreach ($data['users'] ?? [] as $user) {
                $usersByEmail[mb_strtolower((string) ($user['email'] ?? ''))] = $user;
            }

            $minimalUsers = [];
            foreach ($requiredAccounts as $email => [$role, $passwordVariable]) {
                $user = $usersByEmail[$email] ?? null;
                if ($user === null || ($user['role'] ?? '') !== $role) {
                    throw new \RuntimeException('Minimal seed account is missing or has an unexpected role: ' . $email);
                }

                $password = (string) env($passwordVariable, '');
                if (\strlen($password) < 12) {
                    throw new \RuntimeException($passwordVariable . ' must be set to a password of at least 12 characters.');
                }

                $user['attendedEvents'] = [];
                $user['orientationModules'] = [];
                $user['createdAt'] = date('Y-m-d H:i:s');
                $user['requestedAt'] = $user['createdAt'];
                $user['lastLogin'] = null;
                $minimalUsers[] = $user;
                $minimalPasswords[$email] = password_hash($password, PASSWORD_DEFAULT);
            }

            $data['users'] = $minimalUsers;
        }

        if ($fresh) {
            DB::table('support_messages')->delete();
            DB::table('support_tickets')->delete();
            DB::table('document_request_events')->delete();
            DB::table('document_requests')->delete();
            DB::table('lost_found_claims')->delete();
            DB::table('lost_found_items')->delete();
            DB::table('orientation_quiz_answers')->delete();
            DB::table('orientation_quiz_attempts')->delete();
            DB::table('orientation_quiz_options')->delete();
            DB::table('orientation_quiz_questions')->delete();
            DB::table('orientation_quizzes')->delete();
            DB::table('orientation_progress')->delete();
            DB::table('orientation_modules')->delete();
            DB::table('announcements')->delete();
            DB::table('regulations')->delete();
            DB::table('events')->delete();
            DB::table('broadcasts')->delete();
            DB::table('reports')->delete();
            DB::table('tracking')->delete();
            DB::table('audit_log')->delete();
            DB::table('event_attendance')->delete();
            DB::table('remember_tokens')->delete();
            DB::table('password_resets')->delete();
            DB::table('login_attempts')->delete();
            DB::table('notifications')->delete();
            DB::table('files')->delete();
            DB::table('users')->delete();
            // Restart the identity sequences so re-seeded rows start at id 1
            // again (PostgreSQL equivalent of ALTER TABLE ... AUTO_INCREMENT).
            DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), 1, false)");
            DB::statement("SELECT setval(pg_get_serial_sequence('audit_log', 'id'), 1, false)");
            DB::statement("SELECT setval(pg_get_serial_sequence('files', 'id'), 1, false)");
            DB::statement("SELECT setval(pg_get_serial_sequence('notifications', 'id'), 1, false)");
        }

        DB::transaction(function () use ($data, $minimal, $minimalPasswords): void {
            if (DB::table('users')->count() > 0) {
                echo "Users already exist — skipped. Use --fresh to reseed.\n";

                return;
            }

            $defaultPasswordHash = password_hash((string) ($data['meta']['demoPassword'] ?? 'Beacon@2026'), PASSWORD_DEFAULT);
            $toDb = static function (?string $value): ?string {
                if ($value === null || trim($value) === '') {
                    return null;
                }

                $timestamp = strtotime($value);

                return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
            };
            $json = static fn (mixed $value): ?string => $value === null || $value === []
                ? null
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($data['users'] ?? [] as $user) {
                $email = mb_strtolower((string) $user['email']);
                DB::table('users')->insert([
                    'public_id' => $user['id'],
                    'first_name' => $user['firstName'],
                    'last_name' => $user['lastName'],
                    'email' => $email,
                    'username' => $user['username'],
                    'password_hash' => $minimal ? $minimalPasswords[$email] : $defaultPasswordHash,
                    'role' => $user['role'],
                    'student_id' => $user['studentId'] ?: null,
                    'department' => $user['department'] ?: null,
                    'program' => $user['program'] ?: null,
                    'year_level' => $user['year'] ?: null,
                    'access_level' => $user['accessLevel'] ?: null,
                    'account_tier' => $user['accountTier'] ?: 'Student Tier',
                    'status' => $user['status'] ?: 'Pending',
                    'phone' => $user['phone'] ?: null,
                    'created_at' => $user['createdAt'] ?: date('Y-m-d H:i:s'),
                    'updated_at' => $user['createdAt'] ?: date('Y-m-d H:i:s'),
                    'requested_at' => $user['requestedAt'] ?: ($user['createdAt'] ?: date('Y-m-d H:i:s')),
                    'last_login_at' => $user['lastLogin'] ?: null,
                ]);
            }

            if ($minimal) {
                echo 'Seeded ' . count($data['users'] ?? []) . " accounts only; demo content and activity records were skipped.\n";

                return;
            }

            foreach ($data['auditLog'] ?? [] as $entry) {
                DB::table('audit_log')->insert([
                    'actor_user_id' => null,
                    'actor_name' => 'System',
                    'target_user_id' => null,
                    'action' => $entry['action'] ?? '',
                    'entity_type' => $entry['entity'] ?? '',
                    'entity_id' => $entry['entityId'] ?? '',
                    'title' => $entry['title'] ?? 'System event',
                    'text' => $entry['text'] ?? '',
                    'tone' => $entry['tone'] ?? 'brand',
                    'icon' => $entry['icon'] ?? 'info',
                    'ip_address' => null,
                    'user_agent' => null,
                    'created_at' => $entry['at'] ?? date('Y-m-d H:i:s'),
                ]);
            }

            $userRows = DB::table('users')
                ->select('id', 'email', 'first_name', 'last_name', 'student_id', 'program', 'year_level', 'role', 'status')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all();
            $userByEmail = [];
            $coordinatorId = null;
            $coordinatorName = 'OSAS Administration';

            foreach ($userRows as $row) {
                $userByEmail[mb_strtolower((string) $row['email'])] = $row;

                if ($row['role'] === 'Coordinator' && $coordinatorId === null) {
                    $coordinatorId = (int) $row['id'];
                    $coordinatorName = trim($row['first_name'] . ' ' . $row['last_name']);
                }
            }

            $now = date('Y-m-d H:i:s');

            foreach ($data['users'] ?? [] as $user) {
                $email = mb_strtolower((string) ($user['email'] ?? ''));
                $userId = (int) ($userByEmail[$email]['id'] ?? 0);

                if ($userId === 0) {
                    continue;
                }

                foreach ($user['attendedEvents'] ?? [] as $attendance) {
                    DB::table('event_attendance')->insert([
                        'user_id' => $userId,
                        'event_code' => $attendance['code'] ?? '',
                        'event_name' => $attendance['name'] ?? '',
                        'attended_at' => $toDb($attendance['date'] ?? null) ?? $now,
                        'credit_hours' => (int) ($attendance['credit'] ?? 0),
                        'created_at' => $toDb($attendance['date'] ?? null) ?? $now,
                    ]);
                }
            }

            foreach ($data['announcements'] ?? [] as $announcement) {
                DB::table('announcements')->insert([
                    'public_id' => $announcement['id'],
                    'title' => $announcement['title'],
                    'category' => $announcement['category'] ?? '',
                    'audience' => $announcement['audience'] ?? 'All Student Portals',
                    'status' => $announcement['status'] ?? 'Draft',
                    'priority' => $announcement['priority'] ?? 'Standard Priority',
                    'summary' => $announcement['summary'] ?? null,
                    'body' => $announcement['body'] ?? null,
                    'attachments' => $json($announcement['attachments'] ?? null),
                    'author' => $announcement['author'] ?? $coordinatorName,
                    'views' => (int) ($announcement['views'] ?? 0),
                    'published_at' => $toDb($announcement['publishedAt'] ?? null),
                    'scheduled_for' => $toDb($announcement['scheduledFor'] ?? null),
                    'created_by' => $coordinatorId,
                    'created_at' => $toDb($announcement['date'] ?? null) ?? $now,
                    'updated_at' => $toDb($announcement['updatedAt'] ?? null) ?? $now,
                ]);
            }

            foreach ($data['regulations'] ?? [] as $regulation) {
                DB::table('regulations')->insert([
                    'public_id' => $regulation['id'],
                    'reference_code' => $regulation['referenceCode'] ?? $regulation['code'] ?? '',
                    'title' => $regulation['title'],
                    'category' => $regulation['category'] ?? '',
                    'audience' => $regulation['audience'] ?? 'All Students',
                    'status' => $regulation['status'] ?? 'Draft',
                    'effective_date' => $regulation['effectiveDate'] ?? '',
                    'review_schedule' => $regulation['reviewSchedule'] ?? '',
                    'approval_body' => $regulation['approvalBody'] ?? '',
                    'views' => (int) ($regulation['views'] ?? 0),
                    'summary' => $regulation['summary'] ?? null,
                    'body' => $regulation['body'] ?? null,
                    'sanctions' => $json($regulation['sanctions'] ?? null),
                    'created_by' => $coordinatorId,
                    'created_at' => $toDb($regulation['updatedAt'] ?? null) ?? $now,
                    'updated_at' => $toDb($regulation['updatedAt'] ?? null) ?? $now,
                ]);
            }

            foreach ($data['modules'] ?? [] as $module) {
                DB::table('orientation_modules')->insert([
                    'public_id' => $module['id'],
                    'title' => $module['title'],
                    'category' => $module['category'] ?? '',
                    'status' => $module['status'] ?? 'Draft',
                    'audience' => $module['audience'] ?? 'Incoming Freshmen',
                    'version' => $module['version'] ?? 'v1.0',
                    'duration_minutes' => (int) ($module['duration'] ?? 0),
                    'verification_method' => 'Quiz Assessment',
                    'passing_rate' => 80,
                    'mandatory' => !empty($module['mandatory']) ? 1 : 0,
                    'learners' => (int) ($module['learners'] ?? 0),
                    'completions' => (int) ($module['completions'] ?? 0),
                    'completion_rate' => (float) ($module['completionRate'] ?? 0),
                    'description' => $module['description'] ?? null,
                    'objectives' => $json($module['objectives'] ?? null),
                    'body' => $module['body'] ?? null,
                    'department' => $module['department'] ?? '',
                    'attachments_count' => (int) ($module['attachments'] ?? 0),
                    'created_by' => $coordinatorId,
                    'created_at' => $toDb($module['updatedAt'] ?? null) ?? $now,
                    'updated_at' => $toDb($module['updatedAt'] ?? null) ?? $now,
                ]);
            }

            $moduleRows = DB::table('orientation_modules')
                ->select('id', 'public_id')
                ->orderBy('id')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all();
            $studentRows = array_values(array_filter(
                $userRows,
                static fn (array $row): bool => $row['role'] === 'Student' && $row['status'] === 'Active',
            ));

            foreach ($studentRows as $studentIndex => $student) {
                foreach ($moduleRows as $moduleIndex => $module) {
                    $bucket = (crc32((string) $student['email'] . (string) $module['public_id']) % 5);
                    $status = $bucket <= 2 ? 'Completed' : ($bucket === 3 ? 'In Progress' : 'Not Started');
                    $progress = $status === 'Completed' ? 100 : ($status === 'In Progress' ? 55 : 0);

                    DB::table('orientation_progress')->insert([
                        'user_id' => (int) $student['id'],
                        'module_id' => (int) $module['id'],
                        'status' => $status,
                        'progress' => $progress,
                        'last_viewed_at' => $toDb('2026-09-16T09:20:00') ?? $now,
                        'completed_at' => $status === 'Completed' ? ($toDb('2026-09-15T10:00:00') ?? $now) : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach ($data['events'] ?? [] as $event) {
                DB::table('events')->insert([
                    'public_id' => $event['id'],
                    'title' => $event['title'],
                    'category' => $event['category'] ?? '',
                    'status' => $event['status'] ?? 'Scheduled',
                    'start_at' => $toDb($event['startAt'] ?? null) ?? $now,
                    'end_at' => $toDb($event['endAt'] ?? null),
                    'venue' => $event['venue'] ?? '',
                    'mode' => $event['mode'] ?? 'On-campus',
                    'audience' => $event['audience'] ?? 'All Students',
                    'capacity' => (int) ($event['capacity'] ?? 0),
                    'registered' => (int) ($event['registered'] ?? 0),
                    'description' => $event['description'] ?? null,
                    'organizer' => $event['organizer'] ?? '',
                    'verification' => $event['verification'] ?? '',
                    'attachments' => $json($event['attachments'] ?? null),
                    'created_by' => $coordinatorId,
                    'created_at' => $toDb($event['startAt'] ?? null) ?? $now,
                    'updated_at' => $toDb($event['startAt'] ?? null) ?? $now,
                ]);
            }

            $lostFoundIds = [];

            foreach ($data['lostFound'] ?? [] as $item) {
                $reporter = $userByEmail[mb_strtolower((string) ($item['reporterEmail'] ?? ''))] ?? null;

                DB::table('lost_found_items')->insert([
                    'public_id' => $item['id'],
                    'type' => $item['type'] ?? 'Found',
                    'title' => $item['title'],
                    'category' => $item['category'] ?? '',
                    'description' => $item['description'] ?? null,
                    'location' => $item['location'] ?? '',
                    'date_occurred' => $toDb($item['dateOccurred'] ?? null),
                    'reported_by' => $item['reportedBy'] ?? '',
                    'reporter_email' => $item['reporterEmail'] ?? '',
                    'student_id' => $item['studentId'] ?? '',
                    'contact' => $item['contact'] ?? '',
                    'status' => $item['status'] ?? 'Open',
                    'custody' => $item['custody'] ?? '',
                    'attachment' => $item['attachment'] ?? '',
                    'posted_at' => $toDb($item['postedAt'] ?? null) ?? $now,
                    'created_by' => $reporter['id'] ?? $coordinatorId,
                    'created_at' => $toDb($item['postedAt'] ?? null) ?? $now,
                    'updated_at' => $toDb($item['updatedAt'] ?? null) ?? $now,
                ]);

                $lostFoundIds[$item['id']] = (int) DB::table('lost_found_items')->where('public_id', $item['id'])->value('id');

                foreach ($item['claims'] ?? [] as $claim) {
                    $claimant = $userByEmail[mb_strtolower((string) ($claim['claimantEmail'] ?? ''))] ?? null;

                    DB::table('lost_found_claims')->insert([
                        'public_id' => $claim['id'],
                        'item_id' => $lostFoundIds[$item['id']],
                        'claimant_user_id' => $claimant['id'] ?? null,
                        'claimant_name' => $claim['claimantName'] ?? '',
                        'claimant_email' => $claim['claimantEmail'] ?? '',
                        'student_id' => $claim['studentId'] ?? '',
                        'proof' => $claim['proof'] ?? null,
                        'message' => $claim['message'] ?? null,
                        'attachment' => $claim['attachment'] ?? '',
                        'status' => $claim['status'] ?? 'Pending',
                        'verified_by' => $claim['verifiedBy'] ?? '',
                        'verified_at' => $toDb($claim['verifiedAt'] ?? null),
                        'created_at' => $toDb($claim['at'] ?? null) ?? $now,
                    ]);
                }
            }

            foreach ($data['documentRequests'] ?? [] as $request) {
                $student = $userByEmail[mb_strtolower((string) ($request['studentEmail'] ?? ''))] ?? null;

                DB::table('document_requests')->insert([
                    'public_id' => $request['id'],
                    'student_user_id' => $student['id'] ?? null,
                    'student_name' => $request['student'] ?? '',
                    'student_id' => $request['studentId'] ?? '',
                    'student_email' => $request['studentEmail'] ?? '',
                    'program' => $request['program'] ?? '',
                    'year_level' => $request['year'] ?? '',
                    'document_type' => $request['documentType'] ?? '',
                    'purpose' => $request['purpose'] ?? null,
                    'copies' => (int) ($request['copies'] ?? 1),
                    'delivery_mode' => $request['deliveryMode'] ?? 'Pick-up at OSAS',
                    'status' => $request['status'] ?? 'Submitted',
                    'priority' => $request['priority'] ?? 'Medium',
                    'requested_at' => $toDb($request['requestedAt'] ?? null) ?? $now,
                    'updated_at' => $toDb($request['updatedAt'] ?? null) ?? $now,
                    'released_at' => $toDb($request['releasedAt'] ?? null),
                    'processed_by' => $request['processedBy'] ?? 'OSAS Records',
                    'remarks' => $request['remarks'] ?? null,
                    'attachment' => $request['attachment'] ?? '',
                    'created_at' => $toDb($request['requestedAt'] ?? null) ?? $now,
                ]);

                $requestId = (int) DB::table('document_requests')->where('public_id', $request['id'])->value('id');

                foreach ($request['timeline'] ?? [] as $event) {
                    DB::table('document_request_events')->insert([
                        'request_id' => $requestId,
                        'status' => $event['status'] ?? '',
                        'by_name' => $event['by'] ?? '',
                        'note' => $event['note'] ?? null,
                        'created_at' => $toDb($event['at'] ?? null) ?? $now,
                    ]);
                }
            }

            foreach ($data['tickets'] ?? [] as $ticket) {
                $student = $userByEmail[mb_strtolower((string) ($ticket['studentEmail'] ?? ''))] ?? null;

                DB::table('support_tickets')->insert([
                    'public_id' => $ticket['id'],
                    'student_user_id' => $student['id'] ?? null,
                    'student_name' => $ticket['student'] ?? '',
                    'student_email' => $ticket['studentEmail'] ?? '',
                    'student_id' => $ticket['studentId'] ?? '',
                    'program' => $ticket['program'] ?? '',
                    'category' => $ticket['category'] ?? '',
                    'subject' => $ticket['subject'] ?? '',
                    'description' => $ticket['description'] ?? null,
                    'priority' => $ticket['priority'] ?? 'Medium',
                    'status' => $ticket['status'] ?? 'Open',
                    'assigned_to' => $ticket['assignedTo'] ?? '',
                    'rating' => isset($ticket['rating']) && $ticket['rating'] !== '' ? (int) $ticket['rating'] : null,
                    'submitted_at' => $toDb($ticket['submittedAt'] ?? null) ?? $now,
                    'updated_at' => $toDb($ticket['updatedAt'] ?? null) ?? $now,
                    'created_at' => $toDb($ticket['submittedAt'] ?? null) ?? $now,
                ]);

                $ticketId = (int) DB::table('support_tickets')->where('public_id', $ticket['id'])->value('id');

                foreach ($ticket['messages'] ?? [] as $message) {
                    DB::table('support_messages')->insert([
                        'ticket_id' => $ticketId,
                        'author_name' => $message['author'] ?? '',
                        'role' => $message['role'] ?? 'Student',
                        'body' => $message['body'] ?? '',
                        'created_at' => $toDb($message['at'] ?? null) ?? $now,
                    ]);
                }
            }

            foreach ($data['broadcasts'] ?? [] as $broadcast) {
                DB::table('broadcasts')->insert([
                    'public_id' => $broadcast['id'],
                    'subject' => $broadcast['subject'],
                    'message' => $broadcast['message'],
                    'channel' => $broadcast['channel'] ?? 'Portal',
                    'status' => $broadcast['status'] ?? 'Draft',
                    'audience' => $broadcast['audience'] ?? 'All Student Portals',
                    'scheduled_at' => ($broadcast['status'] ?? '') === 'Scheduled' ? $toDb($broadcast['at'] ?? null) : null,
                    'sent_at' => ($broadcast['status'] ?? '') === 'Sent' ? $toDb($broadcast['at'] ?? null) : null,
                    'reach' => (int) ($broadcast['reach'] ?? 0),
                    'delivered' => (int) ($broadcast['delivered'] ?? 0),
                    'opened' => (int) ($broadcast['opened'] ?? 0),
                    'created_by' => $coordinatorId,
                    'created_at' => $toDb($broadcast['at'] ?? null) ?? $now,
                    'updated_at' => $toDb($broadcast['at'] ?? null) ?? $now,
                ]);
            }

            foreach ($data['reports'] ?? [] as $report) {
                DB::table('reports')->insert([
                    'public_id' => $report['id'],
                    'title' => $report['title'],
                    'category' => $report['category'] ?? '',
                    'period' => $report['period'] ?? '',
                    'status' => $report['status'] ?? 'Completed',
                    'format' => $report['format'] ?? 'CSV',
                    'records' => (int) ($report['records'] ?? 0),
                    'generated_at' => $toDb($report['generatedAt'] ?? null) ?? $now,
                    'generated_by' => $report['generatedBy'] ?? $coordinatorName,
                    'scope' => $report['scope'] ?? '',
                    'created_by' => $coordinatorId,
                    'created_at' => $toDb($report['generatedAt'] ?? null) ?? $now,
                    'updated_at' => $toDb($report['generatedAt'] ?? null) ?? $now,
                ]);
            }

            foreach ($data['tracking'] ?? [] as $tracking) {
                DB::table('tracking')->insert([
                    'public_id' => $tracking['id'],
                    'title' => $tracking['title'],
                    'type' => $tracking['type'] ?? '',
                    'views' => (int) ($tracking['views'] ?? 0),
                    'unique_visitors' => (int) ($tracking['uniqueVisitors'] ?? 0),
                    'avg_time' => $tracking['avgTime'] ?? '0m 00s',
                    'engagement' => (float) ($tracking['engagement'] ?? 0),
                    'last_viewed' => $toDb($tracking['lastViewed'] ?? null),
                    'trend' => (float) ($tracking['trend'] ?? 0),
                    'source_id' => $tracking['sourceId'] ?? '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            echo 'Seeded ' . count($data['users'] ?? []) . " users, " . count($data['announcements'] ?? []) . " announcements, "
                . count($data['regulations'] ?? []) . " regulations, " . count($data['modules'] ?? []) . " orientation modules, "
                . count($data['events'] ?? []) . " events, " . count($data['lostFound'] ?? []) . " lost & found items, "
                . count($data['documentRequests'] ?? []) . " document requests, " . count($data['tickets'] ?? []) . " support tickets, "
                . count($data['broadcasts'] ?? []) . " broadcasts, " . count($data['reports'] ?? []) . " reports and "
                . count($data['auditLog'] ?? []) . " audit entries.\n";
            echo 'Demo password: ' . ($data['meta']['demoPassword'] ?? 'Beacon@2026') . "\n";
        });
    }

    private static function publicId(string $table, string $prefix): string
    {
        do {
            $candidate = $prefix . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (DB::table($table)->where('public_id', $candidate)->count() > 0);

        return $candidate;
    }
}
