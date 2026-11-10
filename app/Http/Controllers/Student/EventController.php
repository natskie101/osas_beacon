<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\File;
use App\Services\FileStorage;
use App\Models\Tracking;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;

final class EventController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        $events = Event::forStudent();
        $eventCounts = ['all' => count($events), 'upcoming' => 0, 'ongoing' => 0, 'completed' => 0];

        foreach ($events as $event) {
            $countKey = match ($event['status']) {
                'Scheduled' => 'upcoming',
                'Ongoing' => 'ongoing',
                'Completed' => 'completed',
                default => null,
            };

            if ($countKey !== null) {
                $eventCounts[$countKey]++;
            }
        }

        $search = mb_strtolower((string) $request->string('search'));
        $status = (string) $request->string('status');
        $status = in_array($status, ['Upcoming', 'Ongoing', 'Completed'], true) ? $status : '';

        if ($search !== '') {
            $events = array_values(array_filter(
                $events,
                static fn (array $item): bool => str_contains(mb_strtolower((string) $item['title']), $search)
                    || str_contains(mb_strtolower((string) $item['description']), $search)
                    || str_contains(mb_strtolower((string) $item['venue']), $search)
                    || str_contains(mb_strtolower((string) $item['public_id']), $search),
            ));
        }

        if ($status !== '') {
            $eventStatus = $status === 'Upcoming' ? 'Scheduled' : $status;
            $events = array_values(array_filter(
                $events,
                static fn (array $item): bool => $item['status'] === $eventStatus,
            ));
        }

        return view('student/events/index', [
            'title' => 'Events and Activities',
            'pageTitle' => config('navigation.meta.student.events.title'),
            'pageSubtitle' => config('navigation.meta.student.events.subtitle'),
            'pageKey' => 'events',
            'user' => $actor,
            'role' => $actor['role'],
            'events' => $events,
            'eventCounts' => $eventCounts,
            'filters' => ['search' => (string) $request->string('search'), 'status' => $status],
        ]);
    }

    public function show(Request $request, string $publicId)
    {
        $actor = Auth::user();
        $event = Event::findVisible($publicId);

        if ($event === null) {
            throw new NotFoundHttpException('The requested event could not be found.');
        }

        Tracking::record((string) $event['title'], 'Event', (string) $event['public_id']);

        return view('student/events/show', [
            'title' => $event['title'],
            'pageTitle' => $event['title'],
            'pageSubtitle' => $event['category'] . ' · ' . fmt_datetime($event['start_at']),
            'pageKey' => 'events',
            'user' => $actor,
            'role' => $actor['role'],
            'event' => $event,
            'attachments' => json_items($event['attachments'] ?? null),
        ]);
    }

    public function downloadAttachment(Request $request, string $publicId, string $fileId)
    {
        $event = Event::findVisible($publicId);
        $file = File::find((int) $fileId);

        if ($event === null || $file === null || $file['entity_type'] !== 'event_attachment' || $file['entity_id'] !== $event['public_id']) {
            throw new NotFoundHttpException('The requested event attachment could not be found.');
        }

        $attached = array_filter(
            json_items($event['attachments'] ?? null),
            static fn (array $attachment): bool => (int) ($attachment['file_id'] ?? 0) === (int) $fileId,
        ) !== [];

        if (!$attached) {
            throw new NotFoundHttpException('The requested event attachment could not be found.');
        }

        return FileStorage::download($file);
    }
}
