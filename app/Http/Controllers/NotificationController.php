<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();

        return $this->page('layouts.app', 'notifications/index', [
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

    public function read(Request $request, string $id): Response
    {
        $actor = Auth::user();
        Notification::markRead((int) $id, (int) $actor['id']);

        return Response::redirect(url('/notifications'));
    }

    public function readAll(Request $request): Response
    {
        $actor = Auth::user();
        Notification::markAllRead((int) $actor['id']);
        Session::flash('status', 'All notifications marked as read.');

        return Response::redirect(url('/notifications'));
    }
}
