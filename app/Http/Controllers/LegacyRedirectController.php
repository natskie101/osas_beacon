<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;

final class LegacyRedirectController extends Controller
{
    /** @var array<string, string> */
    private const ADMIN_PAGES = [
        'dashboard' => '/admin/dashboard',
        'announcements' => '/admin/announcements',
        'regulations' => '/admin/regulations',
        'orientation' => '/admin/orientation',
        'events' => '/admin/events',
        'lost-found' => '/admin/lost-found',
        'document-requests' => '/admin/document-requests',
        'analytics' => '/admin/analytics',
        'support' => '/admin/support',
        'users' => '/admin/users',
        'reports' => '/admin/reports',
        'activity' => '/admin/activity',
        'broadcasts' => '/admin/broadcasts',
    ];

    /** @var array<string, string> */
    private const STUDENT_PAGES = [
        'dashboard' => '/student/dashboard',
        'rules' => '/student/rules',
        'orientation' => '/student/orientation',
        'announcements' => '/student/announcements',
        'events' => '/student/events',
        'lost-found' => '/student/lost-found',
        'document-requests' => '/student/document-requests',
        'support' => '/student/support',
    ];

    public function index(Request $request)
    {
        return redirect(url('/'));
    }

    public function coordinator(Request $request, string $page)
    {
        if (!str_ends_with(strtolower($page), '.html')) {
            throw new NotFoundHttpException();
        }

        $key = preg_replace('/\.html$/i', '', $page) ?? $page;

        return redirect(url(self::ADMIN_PAGES[$key] ?? '/admin/dashboard'));
    }

    public function student(Request $request, string $page)
    {
        if (!str_ends_with(strtolower($page), '.html')) {
            throw new NotFoundHttpException();
        }

        $key = preg_replace('/\.html$/i', '', $page) ?? $page;

        return redirect(url(self::STUDENT_PAGES[$key] ?? '/student/dashboard'));
    }
}
