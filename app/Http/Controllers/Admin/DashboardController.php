<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\DocumentRequest;
use App\Models\Event;
use App\Models\LostFoundItem;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $counts = User::counts();
        $recent = AuditLog::recent(6);
        $isCoordinator = $user['role'] === 'Coordinator';

        $announcements = Announcement::counts();
        $documents = DocumentRequest::counts();
        $support = SupportTicket::counts();
        $lostFound = LostFoundItem::counts();

        return $this->page('layouts.app', 'admin/dashboard', [
            'title' => $isCoordinator ? 'Admin Dashboard Overview' : 'Technical Support Dashboard',
            'pageTitle' => $isCoordinator ? 'Admin Dashboard Overview' : 'Technical Support Dashboard',
            'pageSubtitle' => config('navigation.meta.admin.dashboard.subtitle'),
            'pageKey' => 'dashboard',
            'user' => $user,
            'role' => $user['role'],
            'counts' => $counts,
            'recent' => $recent,
            'isCoordinator' => $isCoordinator,
            'operations' => [
                'support_pending' => $support['open'] + $support['in_progress'],
                'support_open' => $support['open'],
                'support_in_progress' => $support['in_progress'],
                'documents_pending' => $documents['submitted'] + $documents['review'] + $documents['ready'],
                'documents_submitted' => $documents['submitted'],
                'documents_review' => $documents['review'],
                'documents_ready' => $documents['ready'],
                'lost_found_pending' => $lostFound['open'] + $lostFound['claim_pending'],
                'lost_found_claims' => $lostFound['claim_pending'],
                'events_upcoming' => Event::upcomingCount(),
                'announcements_published' => $announcements['published'],
            ],
        ]);
    }
}
