<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Regulation;
use App\Services\AuditLogger;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Html;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class RegulationController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $filters = [
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'category' => $request->string('category'),
        ];
        $pagination = Regulation::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Draft', 'Pending Review', 'Published', 'Archived'],
            'newest',
            ['category' => 'category'],
        );

        return $this->page('layouts.app', 'admin/regulations/index', [
            'title' => 'Rules & Regulations',
            'pageTitle' => config('navigation.meta.admin.regulations.title'),
            'pageSubtitle' => config('navigation.meta.admin.regulations.subtitle'),
            'pageKey' => 'regulations',
            'user' => $actor,
            'role' => $actor['role'],
            'regulations' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => Regulation::counts(),
            'categories' => Regulation::categories(),
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

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        $data['body'] = Html::clean((string) $data['body']);
        $data['summary'] = trim((string) ($data['summary'] ?? ''));
        $sanctions = $this->sanctions($request->string('sanctions'));
        $data['sanctions'] = $sanctions === null ? null : json_encode($sanctions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['created_by'] = (int) $actor['id'];
        $id = Regulation::create($data);
        $regulation = Regulation::find($id);

        AuditLogger::log(
            'regulation.created',
            'Regulation Created',
            $regulation['title'] . ' (' . $regulation['public_id'] . ') was created.',
            ['entity_type' => 'regulation', 'entity_id' => (string) $regulation['public_id'], 'tone' => 'blue', 'icon' => 'scale'],
        );

        Session::flash('status', 'Regulation created.');

        return Response::redirect(url('/admin/regulations/' . $regulation['public_id']));
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $regulation = $this->find($publicId);

        return $this->page('layouts.app', 'admin/regulations/show', [
            'title' => $regulation['title'],
            'pageTitle' => $regulation['title'],
            'pageSubtitle' => $regulation['public_id'] . ' · ' . $regulation['status'],
            'pageKey' => 'regulations',
            'user' => $actor,
            'role' => $actor['role'],
            'regulation' => $regulation,
            'sanctions' => json_items($regulation['sanctions'] ?? null),
            'audit' => AuditLog::recent(20),
        ]);
    }

    public function edit(Request $request, string $publicId): Response
    {
        return $this->form(Auth::user(), $this->find($publicId));
    }

    public function update(Request $request, string $publicId): Response
    {
        $regulation = $this->find($publicId);
        $data = $this->validated($request);
<<<<<<< HEAD

        if ($request->string('save_action') === 'draft') {
            $data['status'] = 'Draft';
        } elseif ($request->string('save_action') === 'publish') {
            $data['status'] = 'Published';
        }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        $data['body'] = Html::clean((string) $data['body']);
        $data['summary'] = trim((string) ($data['summary'] ?? ''));
        $sanctions = $this->sanctions($request->string('sanctions'));
        $data['sanctions'] = $sanctions === null ? null : json_encode($sanctions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Regulation::update((int) $regulation['id'], $data);
        $updated = Regulation::find((int) $regulation['id']) ?? $regulation;

        AuditLogger::log(
            'regulation.updated',
            'Regulation Updated',
            $updated['title'] . ' (' . $updated['public_id'] . ') was updated.',
            ['entity_type' => 'regulation', 'entity_id' => (string) $updated['public_id'], 'tone' => 'blue', 'icon' => 'edit'],
        );

        Session::flash('status', 'Regulation saved.');

        return Response::redirect(url('/admin/regulations/' . $updated['public_id']));
    }

    public function publish(Request $request, string $publicId): Response
    {
        $regulation = $this->find($publicId);
        Regulation::update((int) $regulation['id'], ['status' => 'Published']);
        AuditLogger::log(
            'regulation.published',
            'Regulation Published',
            $regulation['title'] . ' is now visible in the student portal.',
            ['entity_type' => 'regulation', 'entity_id' => (string) $regulation['public_id'], 'tone' => 'green', 'icon' => 'scale'],
        );
        Session::flash('status', 'Regulation published.');

        return Response::redirect(url('/admin/regulations/' . $regulation['public_id']));
    }

    public function archive(Request $request, string $publicId): Response
    {
        $regulation = $this->find($publicId);
        Regulation::update((int) $regulation['id'], ['status' => 'Archived']);
        AuditLogger::log(
            'regulation.archived',
            'Regulation Archived',
            $regulation['title'] . ' was archived.',
            ['entity_type' => 'regulation', 'entity_id' => (string) $regulation['public_id'], 'tone' => 'amber', 'icon' => 'fileText'],
        );
        Session::flash('status', 'Regulation archived.');

        return Response::redirect(url('/admin/regulations/' . $regulation['public_id']));
    }

    /** @param array<string, mixed> $actor @param array<string, mixed>|null $regulation */
    private function form(array $actor, ?array $regulation): Response
    {
        $isEdit = $regulation !== null;
        $old = Session::getFlash('old', []);
        $values = $isEdit ? array_merge($regulation, is_array($old) ? $old : []) : array_merge([
            'reference_code' => '',
            'title' => '',
            'category' => '',
<<<<<<< HEAD
            'severity' => 'Minor Offense (Level 1)',
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'audience' => 'All Students',
            'status' => 'Draft',
            'effective_date' => '',
            'review_schedule' => '',
            'approval_body' => 'Office of Student Affairs and Services',
            'summary' => '',
            'body' => '<p></p>',
            'sanctions' => '',
        ], is_array($old) ? $old : []);

        if ($isEdit) {
            $values['sanctions'] = implode("\n", json_items($values['sanctions'] ?? null));
        }

        return $this->page('layouts.app', 'admin/regulations/form', [
            'title' => $isEdit ? 'Edit Regulation' : 'Add Regulation',
            'pageTitle' => $isEdit ? 'Edit Regulation' : 'Add Regulation',
            'pageSubtitle' => $isEdit
                ? 'Update the policy text, review schedule and publication state for ' . $regulation['public_id'] . '.'
                : 'Add a policy or regulation to the student code of conduct library.',
            'pageKey' => 'regulations',
            'user' => $actor,
            'role' => $actor['role'],
            'regulation' => $regulation,
            'values' => $values,
            'errors' => Session::errors(),
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $this->validate($request, [
            'reference_code' => 'required|string|max:50',
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:100',
<<<<<<< HEAD
            'severity' => 'required|in:Minor Offense (Level 1),Major Offense (Level 2),Serious Offense (Level 3),Grave Offense (Level 4)',
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'audience' => 'required|string|max:100',
            'status' => 'required|in:Draft,Pending Review,Published,Archived',
            'effective_date' => 'required|string|max:100',
            'review_schedule' => 'nullable|string|max:150',
            'approval_body' => 'nullable|string|max:200',
<<<<<<< HEAD
            'summary' => 'required|string|max:1000',
=======
            'summary' => 'nullable|string|max:1000',
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'body' => 'required|string',
        ]);
    }

    /** @return list<string>|null */
    private function sanctions(string $input): ?array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $input) ?: [])));

        return $lines === [] ? null : $lines;
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $regulation = Regulation::findByPublicId($publicId);

        if ($regulation === null) {
            throw new NotFoundHttpException('The requested regulation could not be found.');
        }

        return $regulation;
    }
}
