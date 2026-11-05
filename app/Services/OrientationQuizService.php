<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DB;

final class OrientationQuizService
{
    /** @return array<string, mixed>|null */
    public static function findForModule(int $moduleId): ?array
    {
        return DB::first('SELECT * FROM orientation_quizzes WHERE module_id = ? LIMIT 1', [$moduleId]);
    }

    /** @return array<string, mixed>|null */
    public static function findByModulePublicId(string $modulePublicId): ?array
    {
        return DB::first(
            'SELECT q.*, m.public_id AS module_code, m.title AS module_title, m.passing_rate, m.verification_method
             FROM orientation_quizzes q
             JOIN orientation_modules m ON m.id = q.module_id
             WHERE m.public_id = ?
             LIMIT 1',
            [$modulePublicId],
        );
    }

    /** @param array<string, mixed> $data */
    public static function saveSettings(int $moduleId, array $data, int $actorId): int
    {
        $existing = self::findForModule($moduleId);
        $now = date('Y-m-d H:i:s');

        if ($existing === null) {
            return DB::insert(
                'INSERT INTO orientation_quizzes
                    (public_id, module_id, title, instructions, time_limit_minutes, status, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    self::publicId('orientation_quizzes', 'QZ'),
                    $moduleId,
                    $data['title'],
                    $data['instructions'] ?? null,
                    (int) $data['time_limit_minutes'],
                    $data['status'],
                    $actorId,
                    $now,
                    $now,
                ],
            );
        }

        DB::update(
            'UPDATE orientation_quizzes
             SET title = ?, instructions = ?, time_limit_minutes = ?, status = ?, updated_at = ?
             WHERE id = ?',
            [
                $data['title'],
                $data['instructions'] ?? null,
                (int) $data['time_limit_minutes'],
                $data['status'],
                $now,
                (int) $existing['id'],
            ],
        );

        return (int) $existing['id'];
    }

    /** @return list<array<string, mixed>> */
    public static function questions(int $quizId): array
    {
        $questions = DB::select(
            'SELECT * FROM orientation_quiz_questions WHERE quiz_id = ? ORDER BY position ASC, id ASC',
            [$quizId],
        );

        foreach ($questions as $index => $question) {
            $questions[$index]['options'] = self::options((int) $question['id']);
        }

        return $questions;
    }

    /** @return array<string, mixed>|null */
    public static function question(int $questionId, int $quizId): ?array
    {
        $question = DB::first(
            'SELECT * FROM orientation_quiz_questions WHERE id = ? AND quiz_id = ? LIMIT 1',
            [$questionId, $quizId],
        );

        if ($question === null) {
            return null;
        }

        $question['options'] = self::options($questionId);

        return $question;
    }

    /** @return list<array<string, mixed>> */
    public static function options(int $questionId): array
    {
        return DB::select(
            'SELECT * FROM orientation_quiz_options WHERE question_id = ? ORDER BY position ASC, id ASC',
            [$questionId],
        );
    }

    /**
     * Create or replace a question with its options.
     *
     * @param array{prompt:string,points:int,options:list<string>,correct:int} $data
     */
    public static function saveQuestion(int $quizId, array $data, ?int $questionId = null): int
    {
        $now = date('Y-m-d H:i:s');

        if ($questionId === null) {
            $questionId = DB::insert(
                'INSERT INTO orientation_quiz_questions (public_id, quiz_id, prompt, points, position, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    self::publicId('orientation_quiz_questions', 'QQ'),
                    $quizId,
                    $data['prompt'],
                    (int) $data['points'],
                    self::nextQuestionPosition($quizId),
                    $now,
                    $now,
                ],
            );
        } else {
            DB::update(
                'UPDATE orientation_quiz_questions SET prompt = ?, points = ?, updated_at = ? WHERE id = ? AND quiz_id = ?',
                [$data['prompt'], (int) $data['points'], $now, $questionId, $quizId],
            );
            DB::delete('DELETE FROM orientation_quiz_options WHERE question_id = ?', [$questionId]);
        }

        foreach ($data['options'] as $position => $label) {
            DB::insert(
                'INSERT INTO orientation_quiz_options (question_id, label, is_correct, position, created_at)
                 VALUES (?, ?, ?, ?, ?)',
                [$questionId, $label, ((int) $data['correct'] === $position) ? 1 : 0, $position, $now],
            );
        }

        return (int) $questionId;
    }

    public static function deleteQuestion(int $questionId, int $quizId): void
    {
        DB::delete('DELETE FROM orientation_quiz_questions WHERE id = ? AND quiz_id = ?', [$questionId, $quizId]);
    }

    /** @return list<array<string, mixed>> */
    public static function attempts(int $quizId, int $userId): array
    {
        return DB::select(
            'SELECT * FROM orientation_quiz_attempts WHERE quiz_id = ? AND user_id = ? ORDER BY started_at DESC, id DESC',
            [$quizId, $userId],
        );
    }

    /** @return array<string, mixed>|null */
    public static function activeAttempt(int $quizId, int $userId): ?array
    {
        return DB::first(
            'SELECT * FROM orientation_quiz_attempts
             WHERE quiz_id = ? AND user_id = ? AND status = "In Progress"
             ORDER BY id DESC LIMIT 1',
            [$quizId, $userId],
        );
    }

    /** @return array<string, mixed>|null */
    public static function latestAttempt(int $quizId, int $userId): ?array
    {
        return DB::first(
            'SELECT * FROM orientation_quiz_attempts WHERE quiz_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1',
            [$quizId, $userId],
        );
    }

    /** @param array<string, mixed> $quiz */
    public static function startAttempt(array $quiz, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $minutes = max(1, (int) ($quiz['time_limit_minutes'] ?? 20));

        return DB::insert(
            'INSERT INTO orientation_quiz_attempts
                (public_id, quiz_id, user_id, score, total_points, percent, passed, status, started_at, expires_at, submitted_at, created_at, updated_at)
             VALUES (?, ?, ?, 0, 0, 0, 0, "In Progress", ?, ?, NULL, ?, ?)',
            [
                self::publicId('orientation_quiz_attempts', 'QAT'),
                (int) $quiz['id'],
                $userId,
                $now,
                date('Y-m-d H:i:s', strtotime($now . ' +' . $minutes . ' minutes')),
                $now,
                $now,
            ],
        );
    }

    public static function markExpired(int $attemptId): void
    {
        DB::update(
            'UPDATE orientation_quiz_attempts SET status = "Expired", submitted_at = ?, updated_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $attemptId],
        );
    }

    /**
     * Score an attempt and store the answers.
     *
     * @param array<string, mixed> $quiz
     * @param array<string, mixed> $attempt
     * @param array<int, int|null> $answers question ID => option ID
     * @return array{score:int,total:int,percent:float,passed:bool}
     */
    public static function submitAttempt(array $quiz, array $attempt, array $answers): array
    {
        $questions = self::questions((int) $quiz['id']);
        $score = 0;
        $total = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $points = max(1, (int) $question['points']);
            $total += $points;
            $selected = isset($answers[$questionId]) ? (int) $answers[$questionId] : null;
            $correctOption = null;

            foreach ($question['options'] as $option) {
                if ((int) $option['is_correct'] === 1) {
                    $correctOption = (int) $option['id'];
                    break;
                }
            }

            $isCorrect = $selected !== null && $selected === $correctOption;

            if ($isCorrect) {
                $score += $points;
            }

            DB::insert(
                'INSERT INTO orientation_quiz_answers (attempt_id, question_id, option_id, is_correct, created_at)
                 VALUES (?, ?, ?, ?, ?)',
                [(int) $attempt['id'], $questionId, $selected, $isCorrect ? 1 : 0, $now],
            );
        }

        $percent = $total === 0 ? 0.0 : round(($score / $total) * 100, 2);
        $passingRate = (float) ($quiz['passing_rate'] ?? 80);
        $passed = $percent >= $passingRate;

        DB::update(
            'UPDATE orientation_quiz_attempts
             SET score = ?, total_points = ?, percent = ?, passed = ?, status = "Submitted", submitted_at = ?, updated_at = ?
             WHERE id = ?',
            [$score, $total, $percent, $passed ? 1 : 0, $now, $now, (int) $attempt['id']],
        );

        return ['score' => $score, 'total' => $total, 'percent' => $percent, 'passed' => $passed];
    }

    /** @return list<array<string, mixed>> */
    public static function attemptAnswers(int $attemptId): array
    {
        return DB::select(
            'SELECT a.*, q.prompt, q.public_id AS question_code
             FROM orientation_quiz_answers a
             JOIN orientation_quiz_questions q ON q.id = a.question_id
             WHERE a.attempt_id = ?
             ORDER BY q.position ASC, q.id ASC',
            [$attemptId],
        );
    }

    /** @return array<string, mixed>|null */
    public static function attempt(int $attemptId, int $userId): ?array
    {
        return DB::first(
            'SELECT * FROM orientation_quiz_attempts WHERE id = ? AND user_id = ? LIMIT 1',
            [$attemptId, $userId],
        );
    }

    private static function nextQuestionPosition(int $quizId): int
    {
        $max = (int) DB::value('SELECT MAX(position) FROM orientation_quiz_questions WHERE quiz_id = ?', [$quizId]);

        return $max + 1;
    }

    private static function publicId(string $table, string $prefix): string
    {
        do {
            $candidate = $prefix . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ((int) DB::value('SELECT COUNT(*) FROM ' . $table . ' WHERE public_id = ?', [$candidate]) > 0);

        return $candidate;
    }
}
