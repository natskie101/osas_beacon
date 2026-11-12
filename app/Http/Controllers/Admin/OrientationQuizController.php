<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrientationModule;
use App\Services\AuditLogger;
use App\Services\OrientationQuizService;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use Illuminate\Http\Request;

final class OrientationQuizController extends Controller
{
    public function show(Request $request, string $modulePublicId)
    {
        $actor = auth_user();
        $module = $this->module($modulePublicId);
        $quiz = OrientationQuizService::findForModule((int) $module['id']);
        $questions = $quiz !== null ? OrientationQuizService::questions((int) $quiz['id']) : [];

        return view('admin/orientation/quiz', [
            'title' => 'Manage Quiz',
            'pageTitle' => $module['title'] . ' Quiz',
            'pageSubtitle' => $module['public_id'] . ' Â· ' . count($questions) . ' questions',
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'module' => $module,
            'quiz' => $quiz,
            'questions' => $questions,
            'errors' => session('errors', []),
        ]);
    }

    public function saveSettings(Request $request, string $modulePublicId)
    {
        $actor = auth_user();
        $module = $this->module($modulePublicId);
        $data = $this->validate($request, [
            'title' => 'required|string|max:200',
            'instructions' => 'nullable|string|max:2000',
            'time_limit_minutes' => 'required|integer|min:1|max:240',
            'status' => 'required|in:Draft,Published',
        ]);

        OrientationQuizService::saveSettings((int) $module['id'], $data, (int) $actor['id']);

        AuditLogger::log(
            'orientation.quiz_saved',
            'Orientation Quiz Saved',
            $module['title'] . ' quiz settings were updated.',
            ['entity_type' => 'orientation', 'entity_id' => (string) $module['public_id'], 'tone' => 'blue', 'icon' => 'fileText'],
        );

        session()->flash('status', 'Quiz settings saved.');

        return redirect(url('/admin/orientation/' . $module['public_id'] . '/quiz'));
    }

    public function storeQuestion(Request $request, string $modulePublicId)
    {
        $module = $this->module($modulePublicId);
        $quiz = OrientationQuizService::findForModule((int) $module['id']);

        if ($quiz === null) {
            throw new ValidationException(['prompt' => 'Save the quiz settings first.'], $request->input());
        }

        OrientationQuizService::saveQuestion((int) $quiz['id'], $this->questionData($request));
        session()->flash('status', 'Question added.');

        return redirect(url('/admin/orientation/' . $module['public_id'] . '/quiz'));
    }

    public function updateQuestion(Request $request, string $modulePublicId, string $questionId)
    {
        $module = $this->module($modulePublicId);
        $quiz = OrientationQuizService::findForModule((int) $module['id']);

        if ($quiz === null) {
            throw new NotFoundHttpException('The requested quiz could not be found.');
        }

        if (OrientationQuizService::question((int) $questionId, (int) $quiz['id']) === null) {
            throw new NotFoundHttpException('The requested question could not be found.');
        }

        OrientationQuizService::saveQuestion((int) $quiz['id'], $this->questionData($request), (int) $questionId);
        session()->flash('status', 'Question updated.');

        return redirect(url('/admin/orientation/' . $module['public_id'] . '/quiz'));
    }

    public function deleteQuestion(Request $request, string $modulePublicId, string $questionId)
    {
        $module = $this->module($modulePublicId);
        $quiz = OrientationQuizService::findForModule((int) $module['id']);

        if ($quiz !== null) {
            OrientationQuizService::deleteQuestion((int) $questionId, (int) $quiz['id']);
        }

        session()->flash('status', 'Question removed.');

        return redirect(url('/admin/orientation/' . $module['public_id'] . '/quiz'));
    }

    /** @return array{prompt:string,points:int,options:list<string>,correct:int} */
    private function questionData(Request $request): array
    {
        $prompt = trim((string) $request->string('prompt'));
        $points = (int) $request->input('points', 1);
        $rawOptions = $request->input('options', []);
        $correct = (int) $request->input('correct', -1);

        if ($prompt === '') {
            throw new ValidationException(['prompt' => 'Enter the question text.'], $request->input());
        }

        if (!is_array($rawOptions)) {
            $rawOptions = [];
        }

        $options = [];
        $correctIndex = null;

        foreach ($rawOptions as $index => $label) {
            $label = trim((string) $label);

            if ($label === '') {
                continue;
            }

            if ((int) $index === $correct) {
                $correctIndex = count($options);
            }

            $options[] = $label;
        }

        if (count($options) < 2) {
            throw new ValidationException(['options' => 'Provide at least two answer options.'], $request->input());
        }

        if ($correctIndex === null) {
            throw new ValidationException(['correct' => 'Choose the correct answer.'], $request->input());
        }

        return [
            'prompt' => $prompt,
            'points' => max(1, min(100, $points)),
            'options' => $options,
            'correct' => $correctIndex,
        ];
    }

    /** @return array<string, mixed> */
    private function module(string $publicId): array
    {
        $module = OrientationModule::findByPublicId($publicId);

        if ($module === null) {
            throw new NotFoundHttpException('The requested orientation module could not be found.');
        }

        return $module;
    }
}
