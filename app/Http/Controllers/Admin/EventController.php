<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\File;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Support\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Html;
use Illuminate\Http\Request;
use App\Support\Session;

final class EventController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        $filters = [
            'search' => (string) $request->string('search'),
            'status' => (string) $request->string('status'),
            'category' => (string) $request->string('category'),
        ];
        $pagination = Event::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'],
            'newest',
            ['category' => 'category'],
        );

        return view('admin/events/index', [
            'title' => 'Events & Activities',
            'pageTitle' => config('navigation.meta.admin.events.title'),
            'pageSubtitle' => config('navigation.meta.admin.events.subtitle'),
            'pageKey' => 'events',
            'user' => $actor,
            'role' => $actor['role'],
            'events' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => Event::counts(),
            'categories' => $this->categories(),
        ]);
    }

    public function create(Request $request)
    {
        return $this->form(Auth::user(), null);
    }

    public function store(Request $request)
    {
        $actor = Auth::user();
        $data = $this->validated($request);
        $uploaded = $this->storeAttachment($request, null, $actor);
        $data['description'] = trim((string) ($data['description'] ?? ''));
        $data['attachments'] = json_encode($uploaded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['created_by'] = (int) $actor['id'];
        $id = Event::create($data);
        $event = Event::find($id);

        if ($uploaded !== []) {
            DB::table('files')->where('id', (int) $uploaded[0]['file_id'])->update(['entity_id' => (string) $event['public_id']]);
        }

        AuditLogger::log('event.created', 'Event Created', $event['title'] . ' (' . $event['public_id'] . ') was scheduled.', ['entity_type' => 'event', 'entity_id' => (string) $event['public_id'], 'tone' => 'blue', 'icon' => 'calendar']);
        Session::flash('status', 'Event created.');

        return redirect(url('/admin/events/' . $event['public_id']));
    }

    public function show(Request $request, string $publicId)
    {
        $actor = Auth::user();
        $event = $this->find($publicId);

        return view('admin/events/show', [
            'title' => $event['title'],
            'pageTitle' => $event['title'],
            'pageSubtitle' => $event['public_id'] . ' · ' . $event['status'],
            'pageKey' => 'events',
            'user' => $actor,
            'role' => $actor['role'],
            'event' => $event,
            'attachments' => json_items($event['attachments'] ?? null),
            'audit' => AuditLog::recent(20),
        ]);
    }

    public function edit(Request $request, string $publicId)
    {
        return $this->form(Auth::user(), $this->find($publicId));
    }

    public function downloadAttachment(Request $request, string $publicId, string $fileId)
    {
        $event = $this->find($publicId);
        $file = File::find((int) $fileId);
        $attachments = json_items($event['attachments'] ?? null);
        $attached = array_filter(
            $attachments,
            static fn (array $attachment): bool => (int) ($attachment['file_id'] ?? 0) === (int) $fileId,
        ) !== [];

        if (!$attached || $file === null || $file['entity_type'] !== 'event_attachment' || $file['entity_id'] !== $event['public_id']) {
            throw new NotFoundHttpException('The requested event attachment could not be found.');
        }

        return FileStorage::download($file);
    }

    public function update(Request $request, string $publicId)
    {
        $event = $this->find($publicId);
        $data = $this->validated($request);
        $uploaded = $this->storeAttachment($request, $event, Auth::user());
        $data['description'] = trim((string) ($data['description'] ?? ''));
        $attachments = json_items($event['attachments'] ?? null);
        $data['attachments'] = json_encode(array_merge($attachments, $uploaded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Event::update((int) $event['id'], $data);
        $updated = Event::find((int) $event['id']) ?? $event;

        if ($uploaded !== []) {
            DB::table('files')->where('id', (int) $uploaded[0]['file_id'])->update(['entity_id' => (string) $updated['public_id']]);
        }

        AuditLogger::log('event.updated', 'Event Updated', $updated['title'] . ' (' . $updated['public_id'] . ') was updated.', ['entity_type' => 'event', 'entity_id' => (string) $updated['public_id'], 'tone' => 'blue', 'icon' => 'edit']);
        Session::flash('status', 'Event saved.');

        return redirect(url('/admin/events/' . $updated['public_id']));
    }

    public function cancel(Request $request, string $publicId)
    {
        $event = $this->find($publicId);
        Event::update((int) $event['id'], ['status' => 'Cancelled']);
        AuditLogger::log('event.cancelled', 'Event Cancelled', $event['title'] . ' was cancelled.', ['entity_type' => 'event', 'entity_id' => (string) $event['public_id'], 'tone' => 'amber', 'icon' => 'alert']);
        Session::flash('status', 'Event cancelled.');

        return redirect(url('/admin/events/' . $event['public_id']));
    }

    /** @param array<string, mixed> $actor @param array<string, mixed>|null $event */
    private function form(array $actor, ?array $event)
    {
        $isEdit = $event !== null;
        $old = Session::getFlash('old', []);
        $values = $isEdit ? array_merge($event, is_array($old) ? $old : []) : array_merge([
            'title' => '',
            'category' => '',
            'status' => 'Scheduled',
            'start_at' => date('Y-m-d\TH:i', strtotime('+7 days 09:00')),
            'end_at' => date('Y-m-d\TH:i', strtotime('+7 days 11:00')),
            'venue' => '',
            'mode' => 'On-campus',
            'audience' => 'All Students',
            'capacity' => 100,
            'registered' => 0,
            'description' => '',
            'organizer' => 'Office of Student Affairs and Services',
            'verification' => 'OSAS Verified',
        ], is_array($old) ? $old : []);

        if ($isEdit) {
            $values['start_at'] = datetime_input($values['start_at']);
            $values['end_at'] = datetime_input($values['end_at']);
        }

        return view('admin/events/form', [
            'title' => $isEdit ? 'Edit Event' : 'Create Event',
            'pageTitle' => $isEdit ? 'Edit Event' : 'Create Event',
            'pageSubtitle' => $isEdit ? 'Update the schedule, venue and publication details for ' . $event['public_id'] . '.' : 'Schedule a university event or student activity.',
            'pageKey' => 'events',
            'user' => $actor,
            'role' => $actor['role'],
            'event' => $event,
            'attachments' => $event === null ? [] : json_items($event['attachments'] ?? null),
            'values' => $values,
            'errors' => Session::errors(),
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $this->validate($request, [
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:100',
            'status' => 'required|in:Scheduled,Ongoing,Completed,Cancelled',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date',
            'venue' => 'required|string|max:180',
            'mode' => 'required|in:On-campus,Online,Hybrid,Off-campus',
            'audience' => 'required|string|max:100',
            'capacity' => 'required|integer|min:0|max:100000',
            'registered' => 'nullable|integer|min:0|max:100000',
            'description' => 'nullable|string|max:4000',
            'organizer' => 'required|string|max:180',
            'verification' => 'nullable|string|max:200',
        ]);
        $data['start_at'] = db_datetime((string) $data['start_at']);
        $data['end_at'] = !empty($data['end_at']) ? db_datetime((string) $data['end_at']) : null;
        $data['registered'] = (int) ($data['registered'] ?? 0);
        $data['capacity'] = (int) $data['capacity'];

        if ($data['end_at'] !== null && $data['end_at'] < $data['start_at']) {
            throw new ValidationException(['end_at' => 'The end time must be after the start time.'], $request->input());
        }

        return $data;
    }

    /** @return list<string> */
    private function categories(): array
    {
        return array_values(array_filter(
            DB::table('events')
                ->distinct()
                ->where('category', '<>', '')
                ->orderBy('category')
                ->pluck('category')
                ->map(static fn (mixed $category): string => (string) $category)
                ->all(),
        ));
    }

    /**
     * @param array<string, mixed>|null $event
     * @param array<string, mixed> $actor
     * @return list<array{name:string,size:string,file_id:int}>
     */
    private function storeAttachment(Request $request, ?array $event, array $actor): array
    {
        $file = $this->uploadedFile($request, 'attachment');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        try {
            $stored = FileStorage::storeUpload(
                $file,
                'events/' . ($event['public_id'] ?? 'pending'),
                'event_attachment',
                $event['public_id'] ?? 'pending',
                (int) $actor['id'],
            );
        } catch (\RuntimeException $exception) {
            throw new ValidationException(['attachment' => $exception->getMessage()], $request->input());
        }

        return [[
            'name' => (string) $stored['original_name'],
            'size' => format_bytes((int) $stored['size_bytes']),
            'file_id' => (int) $stored['id'],
        ]];
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $event = Event::findByPublicId($publicId);

        if ($event === null) {
            throw new NotFoundHttpException('The requested event could not be found.');
        }

        return $event;
    }
}
