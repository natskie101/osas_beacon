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
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth_user();

        if ($user['role'] === 'Administrator') {
            return redirect(url('/admin/system-overview'));
        }

        $counts = User::counts();
        $recent = AuditLog::recent(6);

        $announcements = Announcement::counts();
        $documents = DocumentRequest::counts();
        $support = SupportTicket::counts();
        $lostFound = LostFoundItem::counts();

        return view('admin/dashboard', [
            'title' => 'Admin Dashboard Overview',
            'pageTitle' => 'Admin Dashboard Overview',
            'pageSubtitle' => config('navigation.meta.admin.dashboard.subtitle'),
            'pageKey' => 'dashboard',
            'user' => $user,
            'role' => $user['role'],
            'counts' => $counts,
            'recent' => $recent,
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
