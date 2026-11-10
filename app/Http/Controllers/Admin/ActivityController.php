<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Auth;
use Illuminate\Http\Request;

final class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        $filters = [
            'search' => (string) $request->string('search'),
            'action' => (string) $request->string('action'),
        ];
        $pagination = AuditLog::paginate($filters, max(1, (int) $request->input('page', 1)), 15);

        return view('admin/activity/index', [
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
