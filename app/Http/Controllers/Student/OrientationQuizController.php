<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\OrientationModule;
use App\Models\OrientationProgress;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\OrientationQuizService;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;

final class OrientationQuizController extends Controller
{
    public function start(Request $request, string $modulePublicId)
    {
        [$module, $quiz] = $this->moduleQuiz($modulePublicId);
        $actor = auth_user();
        $userId = (int) $actor['id'];
        $latest = OrientationQuizService::latestAttempt((int) $quiz['id'], $userId);

        if ($latest !== null && $latest['status'] === 'Submitted' && (int) $latest['passed'] === 1) {
            return redirect(url('/student/orientation/' . $module['public_id'] . '/quiz/result'));
        }

        $active = OrientationQuizService::activeAttempt((int) $quiz['id'], $userId);

        if ($active !== null && strtotime((string) $active['expires_at']) > time()) {
            return redirect(url('/student/orientation/' . $module['public_id'] . '/quiz'));
        }

        if ($active !== null) {
            OrientationQuizService::markExpired((int) $active['id']);
        }

        OrientationQuizService::startAttempt($quiz, $userId);

        return redirect(url('/student/orientation/' . $module['public_id'] . '/quiz'));
    }

    public function show(Request $request, string $modulePublicId)
    {
        [$module, $quiz] = $this->moduleQuiz($modulePublicId);
        $actor = auth_user();
        $attempt = OrientationQuizService::activeAttempt((int) $quiz['id'], (int) $actor['id']);

        if ($attempt === null) {
            return redirect(url('/student/orientation/' . $module['public_id']));
        }

        if (strtotime((string) $attempt['expires_at']) <= time()) {
            OrientationQuizService::markExpired((int) $attempt['id']);

            return redirect(url('/student/orientation/' . $module['public_id'] . '/quiz/result'));
        }

        $questions = OrientationQuizService::questions((int) $quiz['id']);
        shuffle($questions);

        foreach ($questions as $index => $question) {
            $options = $question['options'];
            shuffle($options);
            $questions[$index]['options'] = $options;
        }

        return view('student/orientation/quiz', [
            'title' => $quiz['title'],
            'pageTitle' => $quiz['title'],
            'pageSubtitle' => number_format(count($questions)) . ' questions Â· ' . number_format((int) $quiz['time_limit_minutes']) . ' minutes',
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'module' => $module,
            'quiz' => $quiz,
            'questions' => $questions,
            'attempt' => $attempt,
        ]);
    }

    public function submit(Request $request, string $modulePublicId)
    {
        [$module, $quiz] = $this->moduleQuiz($modulePublicId);
        $actor = auth_user();
        $attempt = OrientationQuizService::activeAttempt((int) $quiz['id'], (int) $actor['id']);

        if ($attempt === null) {
            return redirect(url('/student/orientation/' . $module['public_id']));
        }

        if (strtotime((string) $attempt['expires_at']) <= time()) {
            OrientationQuizService::markExpired((int) $attempt['id']);
            session()->flash('error', 'Time expired. You can retake the assessment.');

            return redirect(url('/student/orientation/' . $module['public_id'] . '/quiz/result'));
        }

        $answers = $request->input('answers', []);
        $answers = is_array($answers) ? $answers : [];
        $result = OrientationQuizService::submitAttempt($quiz, $attempt, $answers);

        if ($result['passed']) {
            $newlyCompleted = OrientationProgress::complete((int) $actor['id'], (int) $module['id']);

            if ($newlyCompleted) {
                NotificationService::send(
                    (int) $actor['id'],
                    'Orientation Module Completed',
                    $module['title'] . ' has been completed with a quiz score of ' . number_format($result['percent'], 1) . '%.',
                    'success',
                    'orientation',
                    (string) $module['public_id'],
                );
                AuditLogger::log(
                    'orientation.quiz_passed',
                    'Orientation Quiz Passed',
                    $module['title'] . ' quiz was passed with ' . number_format($result['percent'], 1) . '%.',
                    ['entity_type' => 'orientation', 'entity_id' => (string) $module['public_id'], 'tone' => 'green', 'icon' => 'checkCircle', 'target_user_id' => (int) $actor['id']],
                );
            }
        }

        session()->flash($result['passed'] ? 'status' : 'error', $result['passed']
            ? 'You passed the assessment with ' . number_format($result['percent'], 1) . '%.'
            : 'You did not meet the passing score. You can retake the assessment.');

        return redirect(url('/student/orientation/' . $module['public_id'] . '/quiz/result'));
    }

    public function result(Request $request, string $modulePublicId)
    {
        [$module, $quiz] = $this->moduleQuiz($modulePublicId);
        $actor = auth_user();
        $attempt = OrientationQuizService::latestAttempt((int) $quiz['id'], (int) $actor['id']);

        if ($attempt === null) {
            return redirect(url('/student/orientation/' . $module['public_id']));
        }

        $questions = OrientationQuizService::questions((int) $quiz['id']);
        $answers = [];

        foreach (OrientationQuizService::attemptAnswers((int) $attempt['id']) as $answer) {
            $answers[(int) $answer['question_id']] = $answer;
        }

        return view('student/orientation/quiz-result', [
            'title' => $quiz['title'] . ' Result',
            'pageTitle' => 'Assessment Result',
            'pageSubtitle' => $module['title'],
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'module' => $module,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $answers,
        ]);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    private function moduleQuiz(string $modulePublicId): array
    {
        $module = OrientationModule::findPublished($modulePublicId);

        if ($module === null) {
            throw new NotFoundHttpException('The requested orientation module could not be found.');
        }

        $quiz = OrientationQuizService::findForModule((int) $module['id']);

        if ($quiz === null || $quiz['status'] !== 'Published') {
            throw new NotFoundHttpException('The requested assessment is not available.');
        }

        return [$module, $quiz];
    }
}
