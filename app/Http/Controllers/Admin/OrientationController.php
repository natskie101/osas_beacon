<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OrientationModule;
use App\Services\AuditLogger;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Html;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

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
        $data['body'] = Html::clean((string) $data['body']);
        $objectives = $this->objectives($request->string('objectives'));
        $data['objectives'] = $objectives === null ? null : json_encode($objectives, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['created_by'] = (int) $actor['id'];
        $id = OrientationModule::create($data);
        $module = OrientationModule::find($id);

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

        return $this->page('layouts.app', 'admin/orientation/show', [
            'title' => $module['title'],
            'pageTitle' => $module['title'],
            'pageSubtitle' => $module['public_id'] . ' · ' . $module['status'],
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'module' => $module,
            'objectives' => json_items($module['objectives'] ?? null),
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
        $data['body'] = Html::clean((string) $data['body']);
        $objectives = $this->objectives($request->string('objectives'));
        $data['objectives'] = $objectives === null ? null : json_encode($objectives, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        OrientationModule::update((int) $module['id'], $data);
        $updated = OrientationModule::find((int) $module['id']) ?? $module;

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

    /** @param array<string, mixed> $actor @param array<string, mixed>|null $module */
    private function form(array $actor, ?array $module): Response
    {
        $isEdit = $module !== null;
        $old = Session::getFlash('old', []);
        $values = $isEdit ? array_merge($module, is_array($old) ? $old : []) : array_merge([
            'title' => '',
            'category' => '',
            'status' => 'Draft',
            'audience' => 'Incoming Freshmen',
            'version' => 'v1.0',
            'duration_minutes' => 15,
            'mandatory' => 1,
            'description' => '',
            'objectives' => '',
            'body' => '<p></p>',
            'department' => 'Office of Student Affairs and Services',
            'attachments_count' => 0,
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
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $this->validate($request, [
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:120',
            'status' => 'required|in:Draft,Published,Archived',
            'audience' => 'required|string|max:100',
            'version' => 'required|string|max:20',
            'duration_minutes' => 'required|integer|min:1|max:600',
            'mandatory' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'body' => 'required|string',
            'department' => 'nullable|string|max:150',
            'attachments_count' => 'nullable|integer|min:0|max:100',
        ]);
        $data['mandatory'] = !empty($data['mandatory']) ? 1 : 0;
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
