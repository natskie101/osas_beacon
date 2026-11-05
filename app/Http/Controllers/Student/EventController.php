<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Tracking;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Request;
use App\Support\Response;

final class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $events = Event::forStudent();
        $search = mb_strtolower($request->string('search'));

        if ($search !== '') {
            $events = array_values(array_filter(
                $events,
                static fn (array $item): bool => str_contains(mb_strtolower((string) $item['title']), $search)
                    || str_contains(mb_strtolower((string) $item['description']), $search)
                    || str_contains(mb_strtolower((string) $item['venue']), $search),
            ));
        }

        return $this->page('layouts.app', 'student/events/index', [
            'title' => 'Events and Activities',
            'pageTitle' => config('navigation.meta.student.events.title'),
            'pageSubtitle' => config('navigation.meta.student.events.subtitle'),
            'pageKey' => 'events',
            'user' => $actor,
            'role' => $actor['role'],
            'events' => $events,
            'filters' => ['search' => $request->string('search')],
        ]);
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $event = Event::findVisible($publicId);

        if ($event === null) {
            throw new NotFoundHttpException('The requested event could not be found.');
        }

        Tracking::record((string) $event['title'], 'Event', (string) $event['public_id']);

        return $this->page('layouts.app', 'student/events/show', [
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
}
