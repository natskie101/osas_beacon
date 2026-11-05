<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
<<<<<<< HEAD
use App\Models\File;
use App\Models\OrientationModule;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Services\OrientationQuizService;
use App\Support\Auth;
use App\Support\DB;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use RuntimeException;
=======
use App\Models\OrientationModule;
use App\Services\AuditLogger;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Html;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37

final class OrientationController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $filters = [
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'category' => $request->string('category'),
        ];
        $pagination = OrientationModule::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Draft', 'Published', 'Archived'],
            'title',
            ['category' => 'category'],
        );

        return $this->page('layouts.app', 'admin/orientation/index', [
            'title' => 'Orientation Content',
            'pageTitle' => config('navigation.meta.admin.orientation.title'),
            'pageSubtitle' => config('navigation.meta.admin.orientation.subtitle'),
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'modules' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => OrientationModule::counts(),
            'categories' => OrientationModule::categories(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(Auth::user(), null);
    }

    public function store(Request $request): Response
    {
        $actor = Auth::user();
        $data = $this->validated($request);
<<<<<<< HEAD

        if ($request->string('save_action') === 'draft') {
            $data['status'] = 'Draft';
        } elseif ($request->string('save_action') === 'publish') {
            $data['status'] = 'Published';
        }

        $data['passing_rate'] = 80;
        $this->normalizeMediaChoice($data, $request);
        $quizData = $this->inlineQuiz($request);
        $media = $this->storeMedia($request, $actor);

        if ($media !== null) {
            $data['media_file_id'] = $media['file_id'];
        }

=======
        $data['body'] = Html::clean((string) $data['body']);
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        $objectives = $this->objectives($request->string('objectives'));
        $data['objectives'] = $objectives === null ? null : json_encode($objectives, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['created_by'] = (int) $actor['id'];
        $id = OrientationModule::create($data);
        $module = OrientationModule::find($id);

<<<<<<< HEAD
        if ($media !== null) {
            DB::update('UPDATE files SET entity_id = ? WHERE id = ?', [$module['public_id'], $media['file_id']]);
        }

        if ($quizData !== null) {
            $quizId = OrientationQuizService::saveSettings((int) $module['id'], [
                'title' => $quizData['title'],
                'instructions' => $quizData['instructions'],
                'time_limit_minutes' => $quizData['time_limit_minutes'],
                'status' => $quizData['status'],
            ], (int) $actor['id']);

            foreach ($quizData['questions'] as $question) {
                OrientationQuizService::saveQuestion($quizId, $question);
            }
        }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        AuditLogger::log(
            'orientation.created',
            'Orientation Module Created',
            $module['title'] . ' (' . $module['public_id'] . ') was created.',
            ['entity_type' => 'orientation', 'entity_id' => (string) $module['public_id'], 'tone' => 'blue', 'icon' => 'graduation'],
        );

        Session::flash('status', 'Orientation module created.');

        return Response::redirect(url('/admin/orientation/' . $module['public_id']));
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $module = $this->find($publicId);
<<<<<<< HEAD
        $quiz = OrientationQuizService::findForModule((int) $module['id']);
        $quizQuestions = $quiz !== null ? OrientationQuizService::questions((int) $quiz['id']) : [];
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37

        return $this->page('layouts.app', 'admin/orientation/show', [
            'title' => $module['title'],
            'pageTitle' => $module['title'],
            'pageSubtitle' => $module['public_id'] . ' · ' . $module['status'],
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'module' => $module,
            'objectives' => json_items($module['objectives'] ?? null),
<<<<<<< HEAD
            'mediaFile' => !empty($module['media_file_id']) ? File::find((int) $module['media_file_id']) : null,
            'quiz' => $quiz,
            'quizQuestionCount' => count($quizQuestions),
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'audit' => AuditLog::recent(20),
        ]);
    }

    public function edit(Request $request, string $publicId): Response
    {
        return $this->form(Auth::user(), $this->find($publicId));
    }

    public function update(Request $request, string $publicId): Response
    {
        $module = $this->find($publicId);
        $data = $this->validated($request);
<<<<<<< HEAD

        if ($request->string('save_action') === 'draft') {
            $data['status'] = 'Draft';
        } elseif ($request->string('save_action') === 'publish') {
            $data['status'] = 'Published';
        }

        $this->normalizeMediaChoice($data, $request);
        $media = $this->storeMedia($request, Auth::user());
        $oldMedia = null;

        if ($media !== null) {
            $data['media_file_id'] = $media['file_id'];

            if (!empty($module['media_file_id'])) {
                $oldMedia = File::find((int) $module['media_file_id']);
            }
        }

=======
        $data['body'] = Html::clean((string) $data['body']);
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        $objectives = $this->objectives($request->string('objectives'));
        $data['objectives'] = $objectives === null ? null : json_encode($objectives, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        OrientationModule::update((int) $module['id'], $data);
        $updated = OrientationModule::find((int) $module['id']) ?? $module;

<<<<<<< HEAD
        if ($media !== null) {
            DB::update('UPDATE files SET entity_id = ? WHERE id = ?', [$updated['public_id'], $media['file_id']]);

            if ($oldMedia !== null && (int) $oldMedia['id'] !== (int) $media['file_id']) {
                FileStorage::delete($oldMedia);
            }
        }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        AuditLogger::log(
            'orientation.updated',
            'Orientation Module Updated',
            $updated['title'] . ' (' . $updated['public_id'] . ') was updated.',
            ['entity_type' => 'orientation', 'entity_id' => (string) $updated['public_id'], 'tone' => 'blue', 'icon' => 'edit'],
        );

        Session::flash('status', 'Orientation module saved.');

        return Response::redirect(url('/admin/orientation/' . $updated['public_id']));
    }

    public function publish(Request $request, string $publicId): Response
    {
        $module = $this->find($publicId);
        OrientationModule::update((int) $module['id'], ['status' => 'Published']);
        AuditLogger::log('orientation.published', 'Orientation Module Published', $module['title'] . ' is now available to students.', ['entity_type' => 'orientation', 'entity_id' => (string) $module['public_id'], 'tone' => 'green', 'icon' => 'graduation']);
        Session::flash('status', 'Orientation module published.');

        return Response::redirect(url('/admin/orientation/' . $module['public_id']));
    }

    public function archive(Request $request, string $publicId): Response
    {
        $module = $this->find($publicId);
        OrientationModule::update((int) $module['id'], ['status' => 'Archived']);
        AuditLogger::log('orientation.archived', 'Orientation Module Archived', $module['title'] . ' was archived.', ['entity_type' => 'orientation', 'entity_id' => (string) $module['public_id'], 'tone' => 'amber', 'icon' => 'fileText']);
        Session::flash('status', 'Orientation module archived.');

        return Response::redirect(url('/admin/orientation/' . $module['public_id']));
    }

<<<<<<< HEAD
    public function downloadMedia(Request $request, string $publicId): Response
    {
        $module = $this->find($publicId);

        if (empty($module['media_file_id'])) {
            throw new NotFoundHttpException('No media file is attached to this module.');
        }

        $file = File::find((int) $module['media_file_id']);

        if ($file === null) {
            throw new NotFoundHttpException('No media file is attached to this module.');
        }

        return FileStorage::download($file);
    }

    /**
     * Parse the optional inline quiz builder on the Add Module form.
     *
     * @return array{title:string,instructions:string,time_limit_minutes:int,status:string,questions:list<array<string,mixed>>}|null
     */
    private function inlineQuiz(Request $request): ?array
    {
        if ($request->string('verification_method') !== 'Quiz Assessment') {
            return null;
        }

        $quiz = $request->input('quiz', []);
        $quiz = is_array($quiz) ? $quiz : [];
        $rawQuestions = $request->input('questions', []);
        $rawQuestions = is_array($rawQuestions) ? $rawQuestions : [];
        $questions = [];

        foreach ($rawQuestions as $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $prompt = trim((string) ($raw['prompt'] ?? ''));

            if ($prompt === '') {
                continue;
            }

            $rawOptions = $raw['options'] ?? [];
            $rawOptions = is_array($rawOptions) ? $rawOptions : [];
            $correct = (int) ($raw['correct'] ?? -1);
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
                throw new ValidationException(['questions' => 'Each question needs at least two answer options.'], $request->all());
            }

            if ($correctIndex === null) {
                throw new ValidationException(['questions' => 'Mark the correct answer for each question.'], $request->all());
            }

            $questions[] = [
                'prompt' => $prompt,
                'points' => max(1, min(100, (int) ($raw['points'] ?? 1))),
                'options' => $options,
                'correct' => $correctIndex,
            ];
        }

        $title = trim((string) ($quiz['title'] ?? ''));

        if ($title === '') {
            $title = trim((string) $request->string('title')) . ' Assessment';
        }

        $status = in_array((string) ($quiz['status'] ?? 'Draft'), ['Draft', 'Published'], true)
            ? (string) ($quiz['status'] ?? 'Draft')
            : 'Draft';

        if ($questions === []) {
            $status = 'Draft';
        }

        return [
            'title' => $title !== '' && $title !== 'Assessment' ? $title : 'Orientation Assessment',
            'instructions' => trim((string) ($quiz['instructions'] ?? '')),
            'time_limit_minutes' => max(1, min(240, (int) ($quiz['time_limit_minutes'] ?? 20))),
            'status' => $status,
            'questions' => $questions,
        ];
    }

    /** @param array<string, mixed> $data */
    private function normalizeMediaChoice(array &$data, Request $request): void
    {
        $contentType = (string) ($data['content_type'] ?? 'File');
        $videoUrl = trim((string) ($data['video_url'] ?? ''));

        if ($contentType === 'Link') {
            if ($videoUrl === '') {
                throw new ValidationException(['video_url' => 'Enter the YouTube or Vimeo link.'], $request->all());
            }

            $data['video_url'] = $videoUrl;

            return;
        }

        if (!in_array($contentType, ['File', 'Link'], true)) {
            $data['content_type'] = 'File';
        }

        $data['video_url'] = '';
    }

    /**
     * @param array<string, mixed> $actor
     * @return array{file_id:int,name:string,size:string}|null
     */
    private function storeMedia(Request $request, array $actor): ?array
    {
        if ($request->string('content_type') === 'Link') {
            return null;
        }

        $file = $request->file('media');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        try {
            $stored = FileStorage::storeUpload(
                $file,
                'orientation-media/pending',
                'orientation_media',
                'pending',
                (int) $actor['id'],
                250 * 1024 * 1024,
                FileStorage::ORIENTATION_MEDIA,
            );
        } catch (RuntimeException $exception) {
            throw new ValidationException(['media' => $exception->getMessage()], $request->all());
        }

        return [
            'file_id' => (int) $stored['id'],
            'name' => (string) $stored['original_name'],
            'size' => format_bytes((int) $stored['size_bytes']),
        ];
    }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    /** @param array<string, mixed> $actor @param array<string, mixed>|null $module */
    private function form(array $actor, ?array $module): Response
    {
        $isEdit = $module !== null;
        $old = Session::getFlash('old', []);
        $values = $isEdit ? array_merge($module, is_array($old) ? $old : []) : array_merge([
            'title' => '',
            'category' => '',
<<<<<<< HEAD
            'content_type' => 'File',
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'status' => 'Draft',
            'audience' => 'Incoming Freshmen',
            'version' => 'v1.0',
            'duration_minutes' => 15,
<<<<<<< HEAD
            'verification_method' => 'Quiz Assessment',
            'passing_rate' => 80,
            'compliance_deadline' => '',
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'mandatory' => 1,
            'description' => '',
            'objectives' => '',
            'body' => '<p></p>',
            'department' => 'Office of Student Affairs and Services',
            'attachments_count' => 0,
<<<<<<< HEAD
            'video_url' => '',
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        ], is_array($old) ? $old : []);

        if ($isEdit) {
            $values['objectives'] = implode("\n", json_items($values['objectives'] ?? null));
        }

        return $this->page('layouts.app', 'admin/orientation/form', [
            'title' => $isEdit ? 'Edit Orientation Module' : 'Add Orientation Module',
            'pageTitle' => $isEdit ? 'Edit Orientation Module' : 'Add Orientation Module',
            'pageSubtitle' => $isEdit
                ? 'Update the learning content and publication state for ' . $module['public_id'] . '.'
                : 'Create a new onboarding module for the student orientation track.',
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'module' => $module,
            'values' => $values,
            'errors' => Session::errors(),
<<<<<<< HEAD
            'mediaFile' => $isEdit && !empty($module['media_file_id']) ? File::find((int) $module['media_file_id']) : null,
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $this->validate($request, [
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:120',
<<<<<<< HEAD
            'content_type' => 'required|in:File,Link,Video Presentation,Document / PDF,Slide Deck,Web URL / Embed',
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'status' => 'required|in:Draft,Published,Archived',
            'audience' => 'required|string|max:100',
            'version' => 'required|string|max:20',
            'duration_minutes' => 'required|integer|min:1|max:600',
<<<<<<< HEAD
            'verification_method' => 'required|in:Quiz Assessment,Digital Acknowledgement',
            'compliance_deadline' => 'nullable|date',
            'mandatory' => 'nullable|boolean',
            'description' => 'required|string|max:1000',
            'department' => 'nullable|string|max:150',
            'attachments_count' => 'nullable|integer|min:0|max:100',
            'video_url' => 'nullable|string|max:500',
        ]);
        $data['mandatory'] = !empty($data['mandatory']) ? 1 : 0;
        $data['compliance_deadline'] = !empty($data['compliance_deadline'])
            ? date('Y-m-d', strtotime((string) $data['compliance_deadline']))
            : null;
=======
            'mandatory' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'body' => 'required|string',
            'department' => 'nullable|string|max:150',
            'attachments_count' => 'nullable|integer|min:0|max:100',
        ]);
        $data['mandatory'] = !empty($data['mandatory']) ? 1 : 0;
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        $data['attachments_count'] = (int) ($data['attachments_count'] ?? 0);

        return $data;
    }

    /** @return list<string>|null */
    private function objectives(string $input): ?array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $input) ?: [])));

        return $lines === [] ? null : $lines;
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $module = OrientationModule::findByPublicId($publicId);

        if ($module === null) {
            throw new NotFoundHttpException('The requested orientation module could not be found.');
        }

        return $module;
    }
}
