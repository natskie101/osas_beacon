<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;

final class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $filters = [
            'search' => $request->string('search'),
            'action' => $request->string('action'),
        ];
        $pagination = AuditLog::paginate($filters, max(1, (int) $request->input('page', 1)), 15);

        return $this->page('layouts.app', 'admin/activity/index', [
            'title' => 'Audit Log',
            'pageTitle' => config('navigation.meta.admin.activity.title'),
            'pageSubtitle' => config('navigation.meta.admin.activity.subtitle'),
            'pageKey' => 'activity',
            'user' => $actor,
            'role' => $actor['role'],
            'entries' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'actions' => AuditLog::actions(),
            'total' => AuditLog::count(),
        ]);
    }
}
