<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tracking;
use App\Support\Auth;
use App\Support\DB;
use App\Support\Request;
use App\Support\Response;

final class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $search = mb_strtolower($request->string('search'));
        $type = $request->string('type');
        $rows = Tracking::all('views DESC, id DESC');

        if ($search !== '') {
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => str_contains(mb_strtolower((string) $row['title']), $search)
                    || str_contains(mb_strtolower((string) $row['type']), $search)
                    || str_contains(mb_strtolower((string) $row['public_id']), $search),
            ));
        }

        if ($type !== '') {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => (string) $row['type'] === $type));
        }

        $types = array_values(array_filter(array_map(
            static fn (array $row): string => (string) $row['type'],
            DB::select('SELECT DISTINCT type FROM tracking WHERE type <> "" ORDER BY type ASC'),
        )));

        $audience = DB::first(
            'SELECT COUNT(*) AS accounts,
                    SUM(role = "Student") AS students,
                    SUM(status = "Active") AS active,
                    SUM(status = "Pending") AS pending
             FROM users',
        );

        return $this->page('layouts.app', 'admin/analytics/index', [
            'title' => 'Viewing & Tracking',
            'pageTitle' => config('navigation.meta.admin.analytics.title'),
            'pageSubtitle' => config('navigation.meta.admin.analytics.subtitle'),
            'pageKey' => 'analytics',
            'user' => $actor,
            'role' => $actor['role'],
            'rows' => $rows,
            'totals' => Tracking::totals(),
            'types' => $types,
            'filters' => ['search' => $request->string('search'), 'type' => $type],
            'audience' => array_map('intval', $audience ?? []),
        ]);
    }
}
