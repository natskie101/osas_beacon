<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class OrientationQuizService
{
    /** @return array<string, mixed>|null */
    public static function findForModule(int $moduleId): ?array
    {
        $quiz = DB::table('orientation_quizzes')
            ->where('module_id', $moduleId)
            ->first();

        return $quiz === null ? null : (array) $quiz;
    }

    /** @return array<string, mixed>|null */
    public static function findByModulePublicId(string $modulePublicId): ?array
    {
        $quiz = DB::table('orientation_quizzes as q')
            ->join('orientation_modules as m', 'm.id', '=', 'q.module_id')
            ->selectRaw('q.*, m.public_id AS module_code, m.title AS module_title, m.passing_rate, m.verification_method')
            ->where('m.public_id', $modulePublicId)
            ->first();

        return $quiz === null ? null : (array) $quiz;
    }

    /** @param array<string, mixed> $data */
    public static function saveSettings(int $moduleId, array $data, int $actorId): int
    {
        $existing = self::findForModule($moduleId);
        $now = date('Y-m-d H:i:s');

        if ($existing === null) {
            return DB::table('orientation_quizzes')->insertGetId([
                'public_id' => self::publicId('orientation_quizzes', 'QZ'),
                'module_id' => $moduleId,
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => (int) $data['time_limit_minutes'],
                'status' => $data['status'],
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('orientation_quizzes')
            ->where('id', (int) $existing['id'])
            ->update([
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => (int) $data['time_limit_minutes'],
                'status' => $data['status'],
                'updated_at' => $now,
            ]);

        return (int) $existing['id'];
    }

    /** @return list<array<string, mixed>> */
    public static function questions(int $quizId): array
    {
        $questions = DB::table('orientation_quiz_questions')
            ->where('quiz_id', $quizId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();

        foreach ($questions as $index => $question) {
            $questions[$index]['options'] = self::options((int) $question['id']);
        }

        return $questions;
    }

    /** @return array<string, mixed>|null */
    public static function question(int $questionId, int $quizId): ?array
    {
        $question = DB::table('orientation_quiz_questions')
            ->where('id', $questionId)
            ->where('quiz_id', $quizId)
            ->first();

        if ($question === null) {
            return null;
        }

        $question = (array) $question;
        $question['options'] = self::options($questionId);

        return $question;
    }

    /** @return list<array<string, mixed>> */
    public static function options(int $questionId): array
    {
        return DB::table('orientation_quiz_options')
            ->where('question_id', $questionId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
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
            $questionId = DB::table('orientation_quiz_questions')->insertGetId([
                'public_id' => self::publicId('orientation_quiz_questions', 'QQ'),
                'quiz_id' => $quizId,
                'prompt' => $data['prompt'],
                'points' => (int) $data['points'],
                'position' => self::nextQuestionPosition($quizId),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('orientation_quiz_questions')
                ->where('id', $questionId)
                ->where('quiz_id', $quizId)
                ->update([
                    'prompt' => $data['prompt'],
                    'points' => (int) $data['points'],
                    'updated_at' => $now,
                ]);
            DB::table('orientation_quiz_options')->where('question_id', $questionId)->delete();
        }

        foreach ($data['options'] as $position => $label) {
            DB::table('orientation_quiz_options')->insert([
                'question_id' => $questionId,
                'label' => $label,
                'is_correct' => ((int) $data['correct'] === $position) ? 1 : 0,
                'position' => $position,
                'created_at' => $now,
            ]);
        }

        return (int) $questionId;
    }

    public static function deleteQuestion(int $questionId, int $quizId): void
    {
        DB::table('orientation_quiz_questions')
            ->where('id', $questionId)
            ->where('quiz_id', $quizId)
            ->delete();
    }

    /** @return list<array<string, mixed>> */
    public static function attempts(int $quizId, int $userId): array
    {
        return DB::table('orientation_quiz_attempts')
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function activeAttempt(int $quizId, int $userId): ?array
    {
        $attempt = DB::table('orientation_quiz_attempts')
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->where('status', 'In Progress')
            ->orderByDesc('id')
            ->first();

        return $attempt === null ? null : (array) $attempt;
    }

    /** @return array<string, mixed>|null */
    public static function latestAttempt(int $quizId, int $userId): ?array
    {
        $attempt = DB::table('orientation_quiz_attempts')
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        return $attempt === null ? null : (array) $attempt;
    }

    /** @param array<string, mixed> $quiz */
    public static function startAttempt(array $quiz, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $minutes = max(1, (int) ($quiz['time_limit_minutes'] ?? 20));

        return DB::table('orientation_quiz_attempts')->insertGetId([
            'public_id' => self::publicId('orientation_quiz_attempts', 'QAT'),
            'quiz_id' => (int) $quiz['id'],
            'user_id' => $userId,
            'score' => 0,
            'total_points' => 0,
            'percent' => 0,
            'passed' => 0,
            'status' => 'In Progress',
            'started_at' => $now,
            'expires_at' => date('Y-m-d H:i:s', strtotime($now . ' +' . $minutes . ' minutes')),
            'submitted_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public static function markExpired(int $attemptId): void
    {
        DB::table('orientation_quiz_attempts')
            ->where('id', $attemptId)
            ->update([
                'status' => 'Expired',
                'submitted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
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

            DB::table('orientation_quiz_answers')->insert([
                'attempt_id' => (int) $attempt['id'],
                'question_id' => $questionId,
                'option_id' => $selected,
                'is_correct' => $isCorrect ? 1 : 0,
                'created_at' => $now,
            ]);
        }

        $percent = $total === 0 ? 0.0 : round(($score / $total) * 100, 2);
        $passingRate = (float) ($quiz['passing_rate'] ?? 80);
        $passed = $percent >= $passingRate;

        DB::table('orientation_quiz_attempts')
            ->where('id', (int) $attempt['id'])
            ->update([
                'score' => $score,
                'total_points' => $total,
                'percent' => $percent,
                'passed' => $passed ? 1 : 0,
                'status' => 'Submitted',
                'submitted_at' => $now,
                'updated_at' => $now,
            ]);

        return ['score' => $score, 'total' => $total, 'percent' => $percent, 'passed' => $passed];
    }

    /** @return list<array<string, mixed>> */
    public static function attemptAnswers(int $attemptId): array
    {
        return DB::table('orientation_quiz_answers as a')
            ->join('orientation_quiz_questions as q', 'q.id', '=', 'a.question_id')
            ->selectRaw('a.*, q.prompt, q.public_id AS question_code')
            ->where('a.attempt_id', $attemptId)
            ->orderBy('q.position')
            ->orderBy('q.id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function attempt(int $attemptId, int $userId): ?array
    {
        $attempt = DB::table('orientation_quiz_attempts')
            ->where('id', $attemptId)
            ->where('user_id', $userId)
            ->first();

        return $attempt === null ? null : (array) $attempt;
    }

    private static function nextQuestionPosition(int $quizId): int
    {
        $max = (int) DB::table('orientation_quiz_questions')
            ->where('quiz_id', $quizId)
            ->max('position');

        return $max + 1;
    }

    private static function publicId(string $table, string $prefix): string
    {
        do {
            $candidate = $prefix . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (DB::table($table)->where('public_id', $candidate)->count() > 0);

        return $candidate;
    }
}
