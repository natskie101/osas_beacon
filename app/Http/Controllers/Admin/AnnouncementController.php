<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\File;
use App\Services\AudienceService;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Html;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use RuntimeException;

final class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $filters = [
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'category' => $request->string('category'),
        ];
        $pagination = Announcement::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Draft', 'Scheduled', 'Published', 'Archived'],
            'date',
            ['category' => 'category'],
        );

        return $this->page('layouts.app', 'admin/announcements/index', [
            'title' => 'Announcements',
            'pageTitle' => config('navigation.meta.admin.announcements.title'),
            'pageSubtitle' => config('navigation.meta.admin.announcements.subtitle'),
            'pageKey' => 'announcements',
            'user' => $actor,
            'role' => $actor['role'],
            'announcements' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => Announcement::counts(),
            'categories' => $this->categories(),
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

        if ($request->string('save_action') === 'draft') {
            $data['status'] = 'Draft';
        }

        $data['body'] = Html::clean((string) $data['body']);
        $data['summary'] = trim((string) ($data['summary'] ?? ''));
        $data['attachments'] = json_encode($this->storeAttachments($request, null, $actor), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['author'] = trim((string) $data['author']);
        $data['created_by'] = (int) $actor['id'];
        $this->normalizeStatus($data);

        $id = Announcement::create($data);
        $announcement = Announcement::find($id);

        if ($announcement === null) {
            throw new RuntimeException('The announcement could not be loaded after creation.');
        }

        foreach (Announcement::decodeList($announcement['attachments'] ?? null) as $attachment) {
            if (!empty($attachment['file_id'])) {
                \App\Support\DB::update(
                    'UPDATE files SET entity_id = ? WHERE id = ?',
                    [$announcement['public_id'], (int) $attachment['file_id']],
                );
            }
        }

        AuditLogger::log(
            'announcement.created',
            'Announcement Created',
            $announcement['title'] . ' (' . $announcement['public_id'] . ') was created as ' . $announcement['status'] . '.',
            ['entity_type' => 'announcement', 'entity_id' => (string) $announcement['public_id'], 'tone' => 'blue', 'icon' => 'megaphone'],
        );

        if ($announcement['status'] === 'Published') {
            $this->announce($announcement);
        }

        Session::flash('status', 'Announcement created successfully.');

        return Response::redirect(url('/admin/announcements/' . $announcement['public_id']));
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $announcement = $this->find($publicId);

        return $this->page('layouts.app', 'admin/announcements/show', [
            'title' => $announcement['title'],
            'pageTitle' => $announcement['title'],
            'pageSubtitle' => $announcement['public_id'] . ' · ' . $announcement['status'],
            'pageKey' => 'announcements',
            'user' => $actor,
            'role' => $actor['role'],
            'announcement' => $announcement,
            'attachments' => $this->attachments($announcement),
            'audit' => AuditLog::recent(20),
        ]);
    }

    public function edit(Request $request, string $publicId): Response
    {
        return $this->form(Auth::user(), $this->find($publicId));
    }

    public function update(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $announcement = $this->find($publicId);
        $data = $this->validated($request);

        if ($request->string('save_action') === 'draft') {
            $data['status'] = 'Draft';
        }

        $data['body'] = Html::clean((string) $data['body']);
        $data['summary'] = trim((string) ($data['summary'] ?? ''));
        $existing = BaseAnnouncementAttachments::from($announcement);
        $uploaded = $this->storeAttachments($request, $announcement, $actor);
        $data['attachments'] = json_encode(array_merge($existing, $uploaded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['author'] = trim((string) $data['author']);
        $this->normalizeStatus($data, $announcement);

        Announcement::update((int) $announcement['id'], $data);
        $updated = Announcement::find((int) $announcement['id']) ?? $announcement;

        AuditLogger::log(
            'announcement.updated',
            'Announcement Updated',
            $updated['title'] . ' (' . $updated['public_id'] . ') was updated.',
            ['entity_type' => 'announcement', 'entity_id' => (string) $updated['public_id'], 'tone' => 'blue', 'icon' => 'edit'],
        );

        if ($updated['status'] === 'Published' && $announcement['status'] !== 'Published') {
            $this->announce($updated);
        }

        Session::flash('status', 'Announcement saved.');

        return Response::redirect(url('/admin/announcements/' . $updated['public_id']));
    }

    public function publish(Request $request, string $publicId): Response
    {
        $announcement = $this->find($publicId);
        Announcement::update((int) $announcement['id'], [
            'status' => 'Published',
            'published_at' => date('Y-m-d H:i:s'),
            'scheduled_for' => null,
        ]);
        $updated = Announcement::find((int) $announcement['id']) ?? $announcement;

        AuditLogger::log(
            'announcement.published',
            'Announcement Published',
            $updated['title'] . ' is now visible in the student portal.',
            ['entity_type' => 'announcement', 'entity_id' => (string) $updated['public_id'], 'tone' => 'green', 'icon' => 'megaphone'],
        );

        $this->announce($updated);
        Session::flash('status', 'Announcement published to the student portal.');

        return Response::redirect(url('/admin/announcements/' . $updated['public_id']));
    }

    public function archive(Request $request, string $publicId): Response
    {
        $announcement = $this->find($publicId);
        Announcement::update((int) $announcement['id'], ['status' => 'Archived']);

        AuditLogger::log(
            'announcement.archived',
            'Announcement Archived',
            $announcement['title'] . ' was archived and removed from the student portal.',
            ['entity_type' => 'announcement', 'entity_id' => (string) $announcement['public_id'], 'tone' => 'amber', 'icon' => 'fileText'],
        );

        Session::flash('status', 'Announcement archived.');

        return Response::redirect(url('/admin/announcements/' . $announcement['public_id']));
    }

    public function removeAttachment(Request $request, string $publicId, string $index): Response
    {
        $announcement = $this->find($publicId);
        $attachments = BaseAnnouncementAttachments::from($announcement);

        if (isset($attachments[(int) $index])) {
            $attachment = $attachments[(int) $index];

            if (!empty($attachment['file_id'])) {
                $file = File::find((int) $attachment['file_id']);
                if ($file !== null) {
                    FileStorage::delete($file);
                }
            }

            unset($attachments[(int) $index]);
            Announcement::update((int) $announcement['id'], [
                'attachments' => $attachments === [] ? null : json_encode(array_values($attachments), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            Session::flash('status', 'Attachment removed.');
        }

        return Response::redirect(url('/admin/announcements/' . $announcement['public_id'] . '/edit'));
    }

    public function downloadAttachment(Request $request, string $publicId, string $fileId): Response
    {
        $announcement = $this->find($publicId);
        $file = File::find((int) $fileId);

        if ($file === null || $file['entity_type'] !== 'announcement' || $file['entity_id'] !== $announcement['public_id']) {
            throw new NotFoundHttpException('The requested announcement attachment could not be found.');
        }

        return FileStorage::download($file);
    }

    /** @param array<string, mixed> $actor @param array<string, mixed>|null $announcement */
    private function form(array $actor, ?array $announcement): Response
    {
        $isEdit = $announcement !== null;
        $old = Session::getFlash('old', []);
        $values = $isEdit ? array_merge($announcement, is_array($old) ? $old : []) : array_merge([
            'title' => '',
            'category' => '',
            'audience' => 'All Student Portals',
            'status' => 'Draft',
            'priority' => 'Standard Priority',
            'summary' => '',
            'body' => '<p></p>',
            'author' => 'OSAS Administration',
            'scheduled_for' => '',
        ], is_array($old) ? $old : []);

        return $this->page('layouts.app', 'admin/announcements/form', [
            'title' => $isEdit ? 'Edit Announcement' : 'Create Announcement',
            'pageTitle' => $isEdit ? 'Edit Announcement' : 'Create Announcement',
            'pageSubtitle' => $isEdit
                ? 'Update ' . $announcement['public_id'] . ' and control its visibility in the student portal.'
                : 'Compose an official notice, choose its audience and decide when it becomes visible.',
            'pageKey' => 'announcements',
            'user' => $actor,
            'role' => $actor['role'],
            'announcement' => $announcement,
            'values' => $values,
            'attachments' => $isEdit ? $this->attachments($announcement) : [],
            'errors' => Session::errors(),
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $this->validate($request, [
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:100',
            'audience' => 'required|in:All Student Portals,Incoming Freshmen,Student Officers,Faculty & Staff,Graduating Students',
            'status' => 'required|in:Draft,Scheduled,Published,Archived',
            'priority' => 'required|in:Standard Priority,High Priority,Urgent,Information',
            'summary' => 'nullable|string|max:1000',
            'body' => 'required|string',
            'author' => 'required|string|max:150',
            'scheduled_for' => 'nullable|date',
        ]);
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $existing */
    private function normalizeStatus(array &$data, ?array $existing = null): void
    {
        if (($data['status'] ?? '') === 'Scheduled' && empty($data['scheduled_for'])) {
            throw new ValidationException(['scheduled_for' => 'Choose a date and time for the scheduled announcement.'], $data);
        }

        if (($data['status'] ?? '') === 'Scheduled' && !empty($data['scheduled_for'])) {
            $data['scheduled_for'] = db_datetime((string) $data['scheduled_for']);
            $data['published_at'] = null;
        } elseif (($data['status'] ?? '') === 'Published') {
            $data['published_at'] = $existing['published_at'] ?? date('Y-m-d H:i:s');
            $data['scheduled_for'] = null;
        } else {
            $data['published_at'] = $existing['published_at'] ?? null;
            $data['scheduled_for'] = !empty($data['scheduled_for']) ? db_datetime((string) $data['scheduled_for']) : null;
        }
    }

    /** @param array<string, mixed> $announcement */
    private function announce(array $announcement): void
    {
        AudienceService::notify(
            (string) $announcement['audience'],
            'New announcement: ' . $announcement['title'],
            Html::summary($announcement['summary'] ?: $announcement['body'], 180),
            'info',
            'announcement',
            (string) $announcement['public_id'],
        );
    }

    /** @return list<array<string, mixed>> */
    private function categories(): array
    {
        $rows = \App\Support\DB::select('SELECT DISTINCT category FROM announcements WHERE category <> "" ORDER BY category ASC');

        return array_map(static fn (array $row): string => (string) $row['category'], $rows);
    }

    /**
     * @param array<string, mixed>|null $announcement
     * @param array<string, mixed> $actor
     * @return list<array<string, mixed>>
     */
    private function storeAttachments(Request $request, ?array $announcement, array $actor): array
    {
        $file = $request->file('attachment');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        try {
            $stored = FileStorage::storeUpload(
                $file,
                'announcements/' . ($announcement['public_id'] ?? 'new'),
                'announcement',
                $announcement['public_id'] ?? 'pending',
                (int) $actor['id'],
            );
        } catch (RuntimeException $exception) {
            throw new ValidationException(['attachment' => $exception->getMessage()], $request->all());
        }

        return [[
            'name' => $stored['original_name'],
            'size' => format_bytes((int) $stored['size_bytes']),
            'file_id' => (int) $stored['id'],
        ]];
    }

    /** @param array<string, mixed> $announcement @return list<array<string, mixed>> */
    private function attachments(array $announcement): array
    {
        $attachments = BaseAnnouncementAttachments::from($announcement);

        foreach ($attachments as $index => $attachment) {
            if (!empty($attachment['file_id'])) {
                $attachments[$index]['file'] = File::find((int) $attachment['file_id']);
            }
        }

        return $attachments;
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $announcement = Announcement::findByPublicId($publicId);

        if ($announcement === null) {
            throw new NotFoundHttpException('The requested announcement could not be found.');
        }

        return $announcement;
    }
}

final class BaseAnnouncementAttachments
{
    /** @param array<string, mixed> $announcement @return list<array<string, mixed>> */
    public static function from(array $announcement): array
    {
        return Announcement::decodeList($announcement['attachments'] ?? null);
    }
}
