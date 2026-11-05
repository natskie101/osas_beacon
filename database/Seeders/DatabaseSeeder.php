<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\DB;

final class DatabaseSeeder
{
    public function run(bool $fresh = false): void
    {
        $path = BASE_PATH . '/database/data/seed.json';

        if (!is_file($path)) {
            throw new \RuntimeException('Seed data not found at database/data/seed.json');
        }

        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if ($fresh) {
            DB::statement('DELETE FROM support_messages');
            DB::statement('DELETE FROM support_tickets');
            DB::statement('DELETE FROM document_request_events');
            DB::statement('DELETE FROM document_requests');
            DB::statement('DELETE FROM lost_found_claims');
            DB::statement('DELETE FROM lost_found_items');
            DB::statement('DELETE FROM orientation_quiz_answers');
            DB::statement('DELETE FROM orientation_quiz_attempts');
            DB::statement('DELETE FROM orientation_quiz_options');
            DB::statement('DELETE FROM orientation_quiz_questions');
            DB::statement('DELETE FROM orientation_quizzes');
            DB::statement('DELETE FROM orientation_progress');
            DB::statement('DELETE FROM orientation_modules');
            DB::statement('DELETE FROM announcements');
            DB::statement('DELETE FROM regulations');
            DB::statement('DELETE FROM events');
            DB::statement('DELETE FROM broadcasts');
            DB::statement('DELETE FROM reports');
            DB::statement('DELETE FROM tracking');
            DB::statement('DELETE FROM audit_log');
            DB::statement('DELETE FROM event_attendance');
            DB::statement('DELETE FROM remember_tokens');
            DB::statement('DELETE FROM password_resets');
            DB::statement('DELETE FROM login_attempts');
            DB::statement('DELETE FROM notifications');
            DB::statement('DELETE FROM files');
            DB::statement('DELETE FROM users');
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE audit_log AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE files AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE notifications AUTO_INCREMENT = 1');
        }

        DB::transaction(function () use ($data): void {
            if ((int) DB::value('SELECT COUNT(*) FROM users') > 0) {
                echo "Users already exist — skipped. Use --fresh to reseed.\n";

                return;
            }

            $passwordHash = password_hash((string) ($data['meta']['demoPassword'] ?? 'Beacon@2026'), PASSWORD_DEFAULT);
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
                DB::insert(
                    'INSERT INTO users
                        (public_id, first_name, last_name, email, username, password_hash, role, student_id,
                         department, program, year_level, access_level, account_tier, status, phone,
                         created_at, updated_at, requested_at, last_login_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $user['id'],
                        $user['firstName'],
                        $user['lastName'],
                        mb_strtolower((string) $user['email']),
                        $user['username'],
                        $passwordHash,
                        $user['role'],
                        $user['studentId'] ?: null,
                        $user['department'] ?: null,
                        $user['program'] ?: null,
                        $user['year'] ?: null,
                        $user['accessLevel'] ?: null,
                        $user['accountTier'] ?: 'Student Tier',
                        $user['status'] ?: 'Pending',
                        $user['phone'] ?: null,
                        $user['createdAt'] ?: date('Y-m-d H:i:s'),
                        $user['createdAt'] ?: date('Y-m-d H:i:s'),
                        $user['requestedAt'] ?: ($user['createdAt'] ?: date('Y-m-d H:i:s')),
                        $user['lastLogin'] ?: null,
                    ],
                );
            }

            foreach ($data['auditLog'] ?? [] as $entry) {
                DB::insert(
                    'INSERT INTO audit_log
                        (actor_user_id, actor_name, target_user_id, action, entity_type, entity_id,
                         title, text, tone, icon, ip_address, user_agent, created_at)
                     VALUES (NULL, ?, NULL, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?)',
                    [
                        'System',
                        $entry['action'] ?? '',
                        $entry['entity'] ?? '',
                        $entry['entityId'] ?? '',
                        $entry['title'] ?? 'System event',
                        $entry['text'] ?? '',
                        $entry['tone'] ?? 'brand',
                        $entry['icon'] ?? 'info',
                        $entry['at'] ?? date('Y-m-d H:i:s'),
                    ],
                );
            }

            $userRows = DB::select(
                'SELECT id, email, first_name, last_name, student_id, program, year_level, role, status FROM users'
            );
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
                    DB::insert(
                        'INSERT INTO event_attendance
                            (user_id, event_code, event_name, attended_at, credit_hours, created_at)
                         VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $userId,
                            $attendance['code'] ?? '',
                            $attendance['name'] ?? '',
                            $toDb($attendance['date'] ?? null) ?? $now,
                            (int) ($attendance['credit'] ?? 0),
                            $toDb($attendance['date'] ?? null) ?? $now,
                        ],
                    );
                }
            }

            foreach ($data['announcements'] ?? [] as $announcement) {
                DB::insert(
                    'INSERT INTO announcements
                        (public_id, title, category, audience, status, priority, summary, body, attachments,
                         author, views, published_at, scheduled_for, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $announcement['id'],
                        $announcement['title'],
                        $announcement['category'] ?? '',
                        $announcement['audience'] ?? 'All Student Portals',
                        $announcement['status'] ?? 'Draft',
                        $announcement['priority'] ?? 'Standard Priority',
                        $announcement['summary'] ?? null,
                        $announcement['body'] ?? null,
                        $json($announcement['attachments'] ?? null),
                        $announcement['author'] ?? $coordinatorName,
                        (int) ($announcement['views'] ?? 0),
                        $toDb($announcement['publishedAt'] ?? null),
                        $toDb($announcement['scheduledFor'] ?? null),
                        $coordinatorId,
                        $toDb($announcement['date'] ?? null) ?? $now,
                        $toDb($announcement['updatedAt'] ?? null) ?? $now,
                    ],
                );
            }

            foreach ($data['regulations'] ?? [] as $regulation) {
                DB::insert(
                    'INSERT INTO regulations
                        (public_id, reference_code, title, category, audience, status, effective_date,
                         review_schedule, approval_body, views, summary, body, sanctions, created_by,
                         created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $regulation['id'],
                        $regulation['referenceCode'] ?? $regulation['code'] ?? '',
                        $regulation['title'],
                        $regulation['category'] ?? '',
                        $regulation['audience'] ?? 'All Students',
                        $regulation['status'] ?? 'Draft',
                        $regulation['effectiveDate'] ?? '',
                        $regulation['reviewSchedule'] ?? '',
                        $regulation['approvalBody'] ?? '',
                        (int) ($regulation['views'] ?? 0),
                        $regulation['summary'] ?? null,
                        $regulation['body'] ?? null,
                        $json($regulation['sanctions'] ?? null),
                        $coordinatorId,
                        $toDb($regulation['updatedAt'] ?? null) ?? $now,
                        $toDb($regulation['updatedAt'] ?? null) ?? $now,
                    ],
                );
            }

            foreach ($data['modules'] ?? [] as $module) {
                DB::insert(
                    'INSERT INTO orientation_modules
                        (public_id, title, category, status, audience, version, duration_minutes, verification_method,
                         passing_rate, mandatory, learners, completions, completion_rate, description, objectives, body,
                         department, attachments_count, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, "Quiz Assessment", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $module['id'],
                        $module['title'],
                        $module['category'] ?? '',
                        $module['status'] ?? 'Draft',
                        $module['audience'] ?? 'Incoming Freshmen',
                        $module['version'] ?? 'v1.0',
                        (int) ($module['duration'] ?? 0),
                        80,
                        !empty($module['mandatory']) ? 1 : 0,
                        (int) ($module['learners'] ?? 0),
                        (int) ($module['completions'] ?? 0),
                        (float) ($module['completionRate'] ?? 0),
                        $module['description'] ?? null,
                        $json($module['objectives'] ?? null),
                        $module['body'] ?? null,
                        $module['department'] ?? '',
                        (int) ($module['attachments'] ?? 0),
                        $coordinatorId,
                        $toDb($module['updatedAt'] ?? null) ?? $now,
                        $toDb($module['updatedAt'] ?? null) ?? $now,
                    ],
                );
            }

            DB::insert(
                'INSERT INTO orientation_modules
                    (public_id, title, category, status, audience, version, duration_minutes, verification_method,
                     passing_rate, mandatory, learners, completions, completion_rate, description, objectives, body,
                     department, attachments_count, video_url, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "Quiz Assessment", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    'ORIENT-012',
                    'Demo: Orientation Assessment Module',
                    'Academic Support',
                    'Published',
                    'Incoming Freshmen',
                    'v1.0',
                    10,
                    80,
                    1,
                    0,
                    0,
                    0,
                    'A short demo module that lets you test the orientation quiz and assessment flow end to end.',
                    json_encode(['Watch the short orientation briefing', 'Complete the demo assessment', 'Review the assessment results'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    '<h4>Demo module</h4><p>This module is for testing the assessment flow. Watch the video and take the quiz.</p>',
                    'Office of Student Affairs and Services',
                    0,
                    'https://www.youtube.com/watch?v=ysz5S6PUM-U',
                    $coordinatorId,
                    $now,
                    $now,
                ],
            );

            $moduleRows = DB::select('SELECT id, public_id FROM orientation_modules ORDER BY id ASC');
            $studentRows = array_values(array_filter(
                $userRows,
                static fn (array $row): bool => $row['role'] === 'Student' && $row['status'] === 'Active',
            ));

            foreach ($studentRows as $studentIndex => $student) {
                foreach ($moduleRows as $moduleIndex => $module) {
                    $bucket = (crc32((string) $student['email'] . (string) $module['public_id']) % 5);
                    $status = $bucket <= 2 ? 'Completed' : ($bucket === 3 ? 'In Progress' : 'Not Started');
                    $progress = $status === 'Completed' ? 100 : ($status === 'In Progress' ? 55 : 0);

                    DB::insert(
                        'INSERT INTO orientation_progress
                            (user_id, module_id, status, progress, last_viewed_at, completed_at, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            (int) $student['id'],
                            (int) $module['id'],
                            $status,
                            $progress,
                            $toDb('2026-09-16T09:20:00') ?? $now,
                            $status === 'Completed' ? ($toDb('2026-09-15T10:00:00') ?? $now) : null,
                            $now,
                            $now,
                        ],
                    );
                }
            }

            $demoModule = DB::first('SELECT id FROM orientation_modules WHERE public_id = "ORIENT-012" LIMIT 1');
            $ryan = DB::first('SELECT id FROM users WHERE email = "ryan.delacruz@uc.edu.ph" LIMIT 1');

            if ($demoModule !== null && $ryan !== null) {
                DB::update(
                    'UPDATE orientation_progress
                     SET status = "Not Started", progress = 0, completed_at = NULL, updated_at = ?
                     WHERE user_id = ? AND module_id = ?',
                    [$now, (int) $ryan['id'], (int) $demoModule['id']],
                );
            }

            $quizModule = DB::first('SELECT id, public_id, title, passing_rate FROM orientation_modules WHERE public_id = "ORIENT-001" LIMIT 1');

            if ($quizModule !== null && $studentRows !== []) {
                $quizId = DB::insert(
                    'INSERT INTO orientation_quizzes
                        (public_id, module_id, title, instructions, time_limit_minutes, status, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 20, "Published", ?, ?, ?)',
                    [
                        self::publicId('orientation_quizzes', 'QZ'),
                        (int) $quizModule['id'],
                        'Orientation Readiness Assessment',
                        'Answer all questions. You need to reach the passing score to complete the module.',
                        $coordinatorId,
                        $now,
                        $now,
                    ],
                );

                $quizQuestions = [
                    ['What is the main purpose of the OSAS student portal?', ['To centralize announcements, orientation and student services', 'To replace classroom instruction', 'To manage staff payroll'], 0],
                    ['Who should students contact for account verification concerns?', ['Office of Student Affairs and Services', 'University cafeteria', 'Sports office'], 0],
                    ['What should a student do if a found item is not theirs?', ['Submit an ownership claim with proof', 'Keep the item', 'Ignore it'], 0],
                    ['Where are official announcements published?', ['In the student portal', 'Only on hallway posters', 'Only through classmates'], 0],
                    ['What is required to complete a mandatory orientation module?', ['Pass the required assessment or acknowledgement', 'Attend one event', 'Submit a document request'], 0],
                ];

                foreach ($quizQuestions as $position => $item) {
                    $questionId = DB::insert(
                        'INSERT INTO orientation_quiz_questions (public_id, quiz_id, prompt, points, position, created_at, updated_at)
                         VALUES (?, ?, ?, 1, ?, ?, ?)',
                        [self::publicId('orientation_quiz_questions', 'QQ'), $quizId, $item[0], $position, $now, $now],
                    );

                    foreach ($item[1] as $optionPosition => $label) {
                        DB::insert(
                            'INSERT INTO orientation_quiz_options (question_id, label, is_correct, position, created_at)
                             VALUES (?, ?, ?, ?, ?)',
                            [$questionId, $label, $item[2] === $optionPosition ? 1 : 0, $optionPosition, $now],
                        );
                    }
                }

                foreach (array_slice($studentRows, 0, 5) as $index => $student) {
                    $percent = [100, 100, 80, 60, 40][$index] ?? 60;
                    $passed = $percent >= (float) $quizModule['passing_rate'] ? 1 : 0;
                    $attemptId = DB::insert(
                        'INSERT INTO orientation_quiz_attempts
                            (public_id, quiz_id, user_id, score, total_points, percent, passed, status, started_at, expires_at, submitted_at, created_at, updated_at)
                         VALUES (?, ?, ?, ?, 5, ?, ?, "Submitted", ?, ?, ?, ?, ?)',
                        [
                            self::publicId('orientation_quiz_attempts', 'QAT'),
                            $quizId,
                            (int) $student['id'],
                            (int) round($percent / 20),
                            $percent,
                            $passed,
                            $now,
                            date('Y-m-d H:i:s', strtotime($now . ' +20 minutes')),
                            $now,
                            $now,
                            $now,
                        ],
                    );

                    $questions = DB::select('SELECT id FROM orientation_quiz_questions WHERE quiz_id = ? ORDER BY id ASC', [$quizId]);

                    foreach ($questions as $questionIndex => $question) {
                        $correctOption = DB::first('SELECT id FROM orientation_quiz_options WHERE question_id = ? AND is_correct = 1 LIMIT 1', [(int) $question['id']]);
                        $isCorrect = $questionIndex < (int) round($percent / 20) ? 1 : 0;
                        $selectedOption = $correctOption['id'] ?? null;

                        if ($isCorrect === 0) {
                            $wrong = DB::first('SELECT id FROM orientation_quiz_options WHERE question_id = ? AND is_correct = 0 LIMIT 1', [(int) $question['id']]);
                            $selectedOption = $wrong['id'] ?? $selectedOption;
                        }

                        DB::insert(
                            'INSERT INTO orientation_quiz_answers (attempt_id, question_id, option_id, is_correct, created_at)
                             VALUES (?, ?, ?, ?, ?)',
                            [$attemptId, (int) $question['id'], $selectedOption, $isCorrect, $now],
                        );
                    }

                    if ($passed === 1) {
                        DB::update(
                            'UPDATE orientation_progress
                             SET status = "Completed", progress = 100, completed_at = ?, updated_at = ?
                             WHERE user_id = ? AND module_id = ?',
                            [$now, $now, (int) $student['id'], (int) $quizModule['id']],
                        );
                    }
                }
            }

            $demoQuizModule = DB::first('SELECT id, public_id, title, passing_rate FROM orientation_modules WHERE public_id = "ORIENT-012" LIMIT 1');

            if ($demoQuizModule !== null) {
                $demoQuizId = DB::insert(
                    'INSERT INTO orientation_quizzes
                        (public_id, module_id, title, instructions, time_limit_minutes, status, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 20, "Published", ?, ?, ?)',
                    [
                        self::publicId('orientation_quizzes', 'QZ'),
                        (int) $demoQuizModule['id'],
                        'Demo Orientation Assessment',
                        'Answer all questions. You need to reach the passing score to complete the demo module.',
                        $coordinatorId,
                        $now,
                        $now,
                    ],
                );

                $demoQuestions = [
                    ['What should you do first before taking the orientation assessment?', ['Watch the orientation video', 'Skip the module', 'Submit a document request'], 0],
                    ['Where can you find official OSAS announcements?', ['In the student portal', 'Only in the cafeteria', 'Only from classmates'], 0],
                    ['What happens after you pass the orientation assessment?', ['The module is marked complete', 'Your account is deleted', 'Nothing changes'], 0],
                ];

                foreach ($demoQuestions as $position => $item) {
                    $questionId = DB::insert(
                        'INSERT INTO orientation_quiz_questions (public_id, quiz_id, prompt, points, position, created_at, updated_at)
                         VALUES (?, ?, ?, 1, ?, ?, ?)',
                        [self::publicId('orientation_quiz_questions', 'QQ'), $demoQuizId, $item[0], $position, $now, $now],
                    );

                    foreach ($item[1] as $optionPosition => $label) {
                        DB::insert(
                            'INSERT INTO orientation_quiz_options (question_id, label, is_correct, position, created_at)
                             VALUES (?, ?, ?, ?, ?)',
                            [$questionId, $label, $item[2] === $optionPosition ? 1 : 0, $optionPosition, $now],
                        );
                    }
                }

                $demoStudents = array_values(array_filter(
                    $studentRows,
                    static fn (array $student): bool => mb_strtolower((string) $student['email']) !== 'ryan.delacruz@uc.edu.ph',
                ));

                foreach (array_slice($demoStudents, 0, 2) as $index => $student) {
                    $percent = [100, 80][$index] ?? 80;
                    $passed = $percent >= (float) $demoQuizModule['passing_rate'] ? 1 : 0;
                    $attemptId = DB::insert(
                        'INSERT INTO orientation_quiz_attempts
                            (public_id, quiz_id, user_id, score, total_points, percent, passed, status, started_at, expires_at, submitted_at, created_at, updated_at)
                         VALUES (?, ?, ?, ?, 3, ?, ?, "Submitted", ?, ?, ?, ?, ?)',
                        [
                            self::publicId('orientation_quiz_attempts', 'QAT'),
                            $demoQuizId,
                            (int) $student['id'],
                            (int) round($percent / 34),
                            $percent,
                            $passed,
                            $now,
                            date('Y-m-d H:i:s', strtotime($now . ' +20 minutes')),
                            $now,
                            $now,
                            $now,
                        ],
                    );

                    $demoQuestionRows = DB::select('SELECT id FROM orientation_quiz_questions WHERE quiz_id = ? ORDER BY id ASC', [$demoQuizId]);

                    foreach ($demoQuestionRows as $questionIndex => $question) {
                        $correctOption = DB::first('SELECT id FROM orientation_quiz_options WHERE question_id = ? AND is_correct = 1 LIMIT 1', [(int) $question['id']]);
                        $isCorrect = $questionIndex < (int) round($percent / 34) ? 1 : 0;
                        $selectedOption = $correctOption['id'] ?? null;

                        if ($isCorrect === 0) {
                            $wrong = DB::first('SELECT id FROM orientation_quiz_options WHERE question_id = ? AND is_correct = 0 LIMIT 1', [(int) $question['id']]);
                            $selectedOption = $wrong['id'] ?? $selectedOption;
                        }

                        DB::insert(
                            'INSERT INTO orientation_quiz_answers (attempt_id, question_id, option_id, is_correct, created_at)
                             VALUES (?, ?, ?, ?, ?)',
                            [$attemptId, (int) $question['id'], $selectedOption, $isCorrect, $now],
                        );
                    }

                    if ($passed === 1) {
                        DB::update(
                            'UPDATE orientation_progress
                             SET status = "Completed", progress = 100, completed_at = ?, updated_at = ?
                             WHERE user_id = ? AND module_id = ?',
                            [$now, $now, (int) $student['id'], (int) $demoQuizModule['id']],
                        );
                    }
                }
            }

            foreach ($data['events'] ?? [] as $event) {
                DB::insert(
                    'INSERT INTO events
                        (public_id, title, category, status, start_at, end_at, venue, mode, audience,
                         capacity, registered, description, organizer, verification, attachments, created_by,
                         created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $event['id'],
                        $event['title'],
                        $event['category'] ?? '',
                        $event['status'] ?? 'Scheduled',
                        $toDb($event['startAt'] ?? null) ?? $now,
                        $toDb($event['endAt'] ?? null),
                        $event['venue'] ?? '',
                        $event['mode'] ?? 'On-campus',
                        $event['audience'] ?? 'All Students',
                        (int) ($event['capacity'] ?? 0),
                        (int) ($event['registered'] ?? 0),
                        $event['description'] ?? null,
                        $event['organizer'] ?? '',
                        $event['verification'] ?? '',
                        $json($event['attachments'] ?? null),
                        $coordinatorId,
                        $toDb($event['startAt'] ?? null) ?? $now,
                        $toDb($event['startAt'] ?? null) ?? $now,
                    ],
                );
            }

            $lostFoundIds = [];

            foreach ($data['lostFound'] ?? [] as $item) {
                $reporter = $userByEmail[mb_strtolower((string) ($item['reporterEmail'] ?? ''))] ?? null;

                DB::insert(
                    'INSERT INTO lost_found_items
                        (public_id, type, title, category, description, location, date_occurred, reported_by,
                         reporter_email, student_id, contact, status, custody, attachment, posted_at,
                         created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $item['id'],
                        $item['type'] ?? 'Found',
                        $item['title'],
                        $item['category'] ?? '',
                        $item['description'] ?? null,
                        $item['location'] ?? '',
                        $toDb($item['dateOccurred'] ?? null),
                        $item['reportedBy'] ?? '',
                        $item['reporterEmail'] ?? '',
                        $item['studentId'] ?? '',
                        $item['contact'] ?? '',
                        $item['status'] ?? 'Open',
                        $item['custody'] ?? '',
                        $item['attachment'] ?? '',
                        $toDb($item['postedAt'] ?? null) ?? $now,
                        $reporter['id'] ?? $coordinatorId,
                        $toDb($item['postedAt'] ?? null) ?? $now,
                        $toDb($item['updatedAt'] ?? null) ?? $now,
                    ],
                );

                $lostFoundIds[$item['id']] = (int) DB::value('SELECT id FROM lost_found_items WHERE public_id = ?', [$item['id']]);

                foreach ($item['claims'] ?? [] as $claim) {
                    $claimant = $userByEmail[mb_strtolower((string) ($claim['claimantEmail'] ?? ''))] ?? null;

                    DB::insert(
                        'INSERT INTO lost_found_claims
                            (public_id, item_id, claimant_user_id, claimant_name, claimant_email, student_id,
                             proof, message, attachment, status, verified_by, verified_at, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $claim['id'],
                            $lostFoundIds[$item['id']],
                            $claimant['id'] ?? null,
                            $claim['claimantName'] ?? '',
                            $claim['claimantEmail'] ?? '',
                            $claim['studentId'] ?? '',
                            $claim['proof'] ?? null,
                            $claim['message'] ?? null,
                            $claim['attachment'] ?? '',
                            $claim['status'] ?? 'Pending',
                            $claim['verifiedBy'] ?? '',
                            $toDb($claim['verifiedAt'] ?? null),
                            $toDb($claim['at'] ?? null) ?? $now,
                        ],
                    );
                }
            }

            foreach ($data['documentRequests'] ?? [] as $request) {
                $student = $userByEmail[mb_strtolower((string) ($request['studentEmail'] ?? ''))] ?? null;

                DB::insert(
                    'INSERT INTO document_requests
                        (public_id, student_user_id, student_name, student_id, student_email, program, year_level,
                         document_type, purpose, copies, delivery_mode, status, priority, requested_at, updated_at,
                         released_at, processed_by, remarks, attachment, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $request['id'],
                        $student['id'] ?? null,
                        $request['student'] ?? '',
                        $request['studentId'] ?? '',
                        $request['studentEmail'] ?? '',
                        $request['program'] ?? '',
                        $request['year'] ?? '',
                        $request['documentType'] ?? '',
                        $request['purpose'] ?? null,
                        (int) ($request['copies'] ?? 1),
                        $request['deliveryMode'] ?? 'Pick-up at OSAS',
                        $request['status'] ?? 'Submitted',
                        $request['priority'] ?? 'Medium',
                        $toDb($request['requestedAt'] ?? null) ?? $now,
                        $toDb($request['updatedAt'] ?? null) ?? $now,
                        $toDb($request['releasedAt'] ?? null),
                        $request['processedBy'] ?? 'OSAS Records',
                        $request['remarks'] ?? null,
                        $request['attachment'] ?? '',
                        $toDb($request['requestedAt'] ?? null) ?? $now,
                    ],
                );

                $requestId = (int) DB::value('SELECT id FROM document_requests WHERE public_id = ?', [$request['id']]);

                foreach ($request['timeline'] ?? [] as $event) {
                    DB::insert(
                        'INSERT INTO document_request_events (request_id, status, by_name, note, created_at)
                         VALUES (?, ?, ?, ?, ?)',
                        [
                            $requestId,
                            $event['status'] ?? '',
                            $event['by'] ?? '',
                            $event['note'] ?? null,
                            $toDb($event['at'] ?? null) ?? $now,
                        ],
                    );
                }
            }

            foreach ($data['tickets'] ?? [] as $ticket) {
                $student = $userByEmail[mb_strtolower((string) ($ticket['studentEmail'] ?? ''))] ?? null;

                DB::insert(
                    'INSERT INTO support_tickets
                        (public_id, student_user_id, student_name, student_email, student_id, program, category,
                         subject, description, priority, status, assigned_to, rating, submitted_at, updated_at, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $ticket['id'],
                        $student['id'] ?? null,
                        $ticket['student'] ?? '',
                        $ticket['studentEmail'] ?? '',
                        $ticket['studentId'] ?? '',
                        $ticket['program'] ?? '',
                        $ticket['category'] ?? '',
                        $ticket['subject'] ?? '',
                        $ticket['description'] ?? null,
                        $ticket['priority'] ?? 'Medium',
                        $ticket['status'] ?? 'Open',
                        $ticket['assignedTo'] ?? '',
                        isset($ticket['rating']) && $ticket['rating'] !== '' ? (int) $ticket['rating'] : null,
                        $toDb($ticket['submittedAt'] ?? null) ?? $now,
                        $toDb($ticket['updatedAt'] ?? null) ?? $now,
                        $toDb($ticket['submittedAt'] ?? null) ?? $now,
                    ],
                );

                $ticketId = (int) DB::value('SELECT id FROM support_tickets WHERE public_id = ?', [$ticket['id']]);

                foreach ($ticket['messages'] ?? [] as $message) {
                    DB::insert(
                        'INSERT INTO support_messages (ticket_id, author_name, role, body, created_at)
                         VALUES (?, ?, ?, ?, ?)',
                        [
                            $ticketId,
                            $message['author'] ?? '',
                            $message['role'] ?? 'Student',
                            $message['body'] ?? '',
                            $toDb($message['at'] ?? null) ?? $now,
                        ],
                    );
                }
            }

            foreach ($data['broadcasts'] ?? [] as $broadcast) {
                DB::insert(
                    'INSERT INTO broadcasts
                        (public_id, subject, message, channel, status, audience, scheduled_at, sent_at,
                         reach, delivered, opened, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $broadcast['id'],
                        $broadcast['subject'],
                        $broadcast['message'],
                        $broadcast['channel'] ?? 'Portal',
                        $broadcast['status'] ?? 'Draft',
                        $broadcast['audience'] ?? 'All Student Portals',
                        ($broadcast['status'] ?? '') === 'Scheduled' ? $toDb($broadcast['at'] ?? null) : null,
                        ($broadcast['status'] ?? '') === 'Sent' ? $toDb($broadcast['at'] ?? null) : null,
                        (int) ($broadcast['reach'] ?? 0),
                        (int) ($broadcast['delivered'] ?? 0),
                        (int) ($broadcast['opened'] ?? 0),
                        $coordinatorId,
                        $toDb($broadcast['at'] ?? null) ?? $now,
                        $toDb($broadcast['at'] ?? null) ?? $now,
                    ],
                );
            }

            foreach ($data['reports'] ?? [] as $report) {
                DB::insert(
                    'INSERT INTO reports
                        (public_id, title, category, period, status, format, records, generated_at, generated_by,
                         scope, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $report['id'],
                        $report['title'],
                        $report['category'] ?? '',
                        $report['period'] ?? '',
                        $report['status'] ?? 'Completed',
                        $report['format'] ?? 'CSV',
                        (int) ($report['records'] ?? 0),
                        $toDb($report['generatedAt'] ?? null) ?? $now,
                        $report['generatedBy'] ?? $coordinatorName,
                        $report['scope'] ?? '',
                        $coordinatorId,
                        $toDb($report['generatedAt'] ?? null) ?? $now,
                        $toDb($report['generatedAt'] ?? null) ?? $now,
                    ],
                );
            }

            foreach ($data['tracking'] ?? [] as $tracking) {
                DB::insert(
                    'INSERT INTO tracking
                        (public_id, title, type, views, unique_visitors, avg_time, engagement, last_viewed,
                         trend, source_id, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $tracking['id'],
                        $tracking['title'],
                        $tracking['type'] ?? '',
                        (int) ($tracking['views'] ?? 0),
                        (int) ($tracking['uniqueVisitors'] ?? 0),
                        $tracking['avgTime'] ?? '0m 00s',
                        (float) ($tracking['engagement'] ?? 0),
                        $toDb($tracking['lastViewed'] ?? null),
                        (float) ($tracking['trend'] ?? 0),
                        $tracking['sourceId'] ?? '',
                        $now,
                        $now,
                    ],
                );
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
        } while ((int) DB::value('SELECT COUNT(*) FROM ' . $table . ' WHERE public_id = ?', [$candidate]) > 0);

        return $candidate;
    }
}
