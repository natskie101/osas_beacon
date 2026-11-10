<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\DocumentRequest;
use App\Models\Event;
use App\Models\OrientationModule;
use App\Models\OrientationProgress;
use App\Models\SupportTicket;
use App\Support\Auth;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = (int) $user['id'];

        $modules = OrientationModule::published();
        $orientation = OrientationProgress::summary($userId, count($modules));

        $announcements = Announcement::published(4);
        $events = Event::upcoming(3);
        $featuredModule = $modules[0] ?? null;

        $tickets = SupportTicket::forStudent($userId);
        $activeTickets = count(array_filter(
            $tickets,
            static fn (array $ticket): bool => in_array($ticket['status'], ['Open', 'In Progress'], true),
        ));

        $requests = DocumentRequest::forStudent($userId);
        $activeRequests = count(array_filter(
            $requests,
            static fn (array $request): bool => in_array($request['status'], ['Submitted', 'Under Review', 'Ready for Pick-up'], true),
        ));

        return view('student/dashboard', [
            'title' => 'Student Dashboard',
            'pageTitle' => 'Student Dashboard',
            'pageSubtitle' => config('navigation.meta.student.dashboard.subtitle'),
            'pageKey' => 'dashboard',
            'user' => $user,
            'role' => $user['role'],
            'orientation' => $orientation,
            'announcementCount' => Announcement::publishedCount(),
            'announcements' => $announcements,
            'featuredModule' => $featuredModule,
            'events' => $events,
            'ticketCount' => count($tickets),
            'activeTickets' => $activeTickets,
            'requestCount' => count($requests),
            'activeRequests' => $activeRequests,
        ]);
    }
}
