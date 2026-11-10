<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TechnicalAdminOverview;
use App\Support\Auth;
use Illuminate\Http\Request;

final class SystemOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        return view('admin/system-overview', [
            'title' => 'System Overview',
            'pageTitle' => config('navigation.meta.admin.system-overview.title'),
            'pageSubtitle' => config('navigation.meta.admin.system-overview.subtitle'),
            'pageKey' => 'system-overview',
            'user' => $user,
            'role' => $user['role'],
            'overview' => TechnicalAdminOverview::data(),
        ]);
    }
}
