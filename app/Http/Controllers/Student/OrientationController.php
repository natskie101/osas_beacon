<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\OrientationModule;
use App\Models\OrientationProgress;
use App\Models\Tracking;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class OrientationController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $modules = OrientationModule::published();
        $progress = OrientationProgress::forUser((int) $actor['id']);
        $summary = OrientationProgress::summary((int) $actor['id'], count($modules));

        return $this->page('layouts.app', 'student/orientation/index', [
            'title' => 'Orientation',
            'pageTitle' => config('navigation.meta.student.orientation.title'),
            'pageSubtitle' => config('navigation.meta.student.orientation.subtitle'),
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'modules' => $modules,
            'progress' => $progress,
            'summary' => $summary,
        ]);
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $module = OrientationModule::findPublished($publicId);

        if ($module === null) {
            throw new NotFoundHttpException('The requested orientation module could not be found.');
        }

        OrientationProgress::markViewed((int) $actor['id'], (int) $module['id']);
        OrientationModule::incrementViews((int) $module['id']);
        Tracking::record((string) $module['title'], 'Orientation', (string) $module['public_id']);
        $progress = OrientationProgress::find((int) $actor['id'], (int) $module['id']);

        return $this->page('layouts.app', 'student/orientation/show', [
            'title' => $module['title'],
            'pageTitle' => $module['title'],
            'pageSubtitle' => $module['category'] . ' · ' . $module['duration_minutes'] . ' min',
            'pageKey' => 'orientation',
            'user' => $actor,
            'role' => $actor['role'],
            'module' => $module,
            'objectives' => json_items($module['objectives'] ?? null),
            'progress' => $progress,
        ]);
    }

    public function complete(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $module = OrientationModule::findPublished($publicId);

        if ($module === null) {
            throw new NotFoundHttpException('The requested orientation module could not be found.');
        }

        $newlyCompleted = OrientationProgress::complete((int) $actor['id'], (int) $module['id']);

        if ($newlyCompleted) {
            NotificationService::send(
                (int) $actor['id'],
                'Orientation Module Completed',
                $module['title'] . ' has been marked complete. Your orientation progress has been updated.',
                'success',
                'orientation',
                (string) $module['public_id'],
            );
            AuditLogger::log(
                'orientation.completed',
                'Orientation Module Completed',
                $module['title'] . ' was marked complete by ' . trim($actor['first_name'] . ' ' . $actor['last_name']) . '.',
                ['entity_type' => 'orientation', 'entity_id' => (string) $module['public_id'], 'tone' => 'green', 'icon' => 'checkCircle', 'target_user_id' => (int) $actor['id']],
            );
            Session::flash('status', 'Module marked complete. Your orientation progress has been updated.');
        } else {
            Session::flash('status', 'This module is already marked complete.');
        }

        return Response::redirect(url('/student/orientation/' . $module['public_id']));
    }
}
