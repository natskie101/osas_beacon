<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Services\AuditLogger;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Html;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $filters = [
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'category' => $request->string('category'),
        ];
        $pagination = Event::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'],
            'newest',
            ['category' => 'category'],
        );

        return $this->page('layouts.app', 'admin/events/index', [
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

    public function create(Request $request): Response
    {
        return $this->form(Auth::user(), null);
    }

    public function store(Request $request): Response
    {
        $actor = Auth::user();
        $data = $this->validated($request);
        $data['description'] = trim((string) ($data['description'] ?? ''));
        $data['created_by'] = (int) $actor['id'];
        $id = Event::create($data);
        $event = Event::find($id);

        AuditLogger::log('event.created', 'Event Created', $event['title'] . ' (' . $event['public_id'] . ') was scheduled.', ['entity_type' => 'event', 'entity_id' => (string) $event['public_id'], 'tone' => 'blue', 'icon' => 'calendar']);
        Session::flash('status', 'Event created.');

        return Response::redirect(url('/admin/events/' . $event['public_id']));
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $event = $this->find($publicId);

        return $this->page('layouts.app', 'admin/events/show', [
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

    public function edit(Request $request, string $publicId): Response
    {
        return $this->form(Auth::user(), $this->find($publicId));
    }

    public function update(Request $request, string $publicId): Response
    {
        $event = $this->find($publicId);
        $data = $this->validated($request);
        $data['description'] = trim((string) ($data['description'] ?? ''));
        Event::update((int) $event['id'], $data);
        $updated = Event::find((int) $event['id']) ?? $event;

        AuditLogger::log('event.updated', 'Event Updated', $updated['title'] . ' (' . $updated['public_id'] . ') was updated.', ['entity_type' => 'event', 'entity_id' => (string) $updated['public_id'], 'tone' => 'blue', 'icon' => 'edit']);
        Session::flash('status', 'Event saved.');

        return Response::redirect(url('/admin/events/' . $updated['public_id']));
    }

    public function cancel(Request $request, string $publicId): Response
    {
        $event = $this->find($publicId);
        Event::update((int) $event['id'], ['status' => 'Cancelled']);
        AuditLogger::log('event.cancelled', 'Event Cancelled', $event['title'] . ' was cancelled.', ['entity_type' => 'event', 'entity_id' => (string) $event['public_id'], 'tone' => 'amber', 'icon' => 'alert']);
        Session::flash('status', 'Event cancelled.');

        return Response::redirect(url('/admin/events/' . $event['public_id']));
    }

    /** @param array<string, mixed> $actor @param array<string, mixed>|null $event */
    private function form(array $actor, ?array $event): Response
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

        return $this->page('layouts.app', 'admin/events/form', [
            'title' => $isEdit ? 'Edit Event' : 'Create Event',
            'pageTitle' => $isEdit ? 'Edit Event' : 'Create Event',
            'pageSubtitle' => $isEdit ? 'Update the schedule, venue and publication details for ' . $event['public_id'] . '.' : 'Schedule a university event or student activity.',
            'pageKey' => 'events',
            'user' => $actor,
            'role' => $actor['role'],
            'event' => $event,
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
            throw new \App\Support\Exceptions\ValidationException(['end_at' => 'The end time must be after the start time.'], $request->all());
        }

        return $data;
    }

    /** @return list<string> */
    private function categories(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) $row['category'],
            \App\Support\DB::select('SELECT DISTINCT category FROM events WHERE category <> "" ORDER BY category ASC'),
        )));
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
