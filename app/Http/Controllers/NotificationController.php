<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth_user();

        return view('notifications/index', [
            'title' => 'Notifications',
            'pageTitle' => 'Notifications',
            'pageSubtitle' => 'Portal alerts, approvals and updates addressed to your account.',
            'pageKey' => 'notifications',
            'user' => $actor,
            'role' => $actor['role'],
            'notifications' => Notification::forUser((int) $actor['id'], 100),
            'unread' => Notification::unreadCount((int) $actor['id']),
        ]);
    }

    public function read(Request $request, string $id)
    {
        $actor = auth_user();
        Notification::markRead((int) $id, (int) $actor['id']);

        return redirect(url('/notifications'));
    }

    public function readAll(Request $request)
    {
        $actor = auth_user();
        Notification::markAllRead((int) $actor['id']);
        session()->flash('status', 'All notifications marked as read.');

        return redirect(url('/notifications'));
    }
}
