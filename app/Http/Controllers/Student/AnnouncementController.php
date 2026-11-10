<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\File;
use App\Models\Tracking;
use App\Services\FileStorage;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;

final class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth_user();
        $search = (string) $request->string('search');
        $category = (string) $request->string('category');
        $announcements = Announcement::published(100);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $announcements = array_values(array_filter(
                $announcements,
                static fn (array $item): bool => str_contains(mb_strtolower((string) $item['title']), $needle)
                    || str_contains(mb_strtolower((string) $item['summary']), $needle)
                    || str_contains(mb_strtolower((string) $item['body']), $needle),
            ));
        }

        if ($category !== '') {
            $announcements = array_values(array_filter(
                $announcements,
                static fn (array $item): bool => (string) $item['category'] === $category,
            ));
        }

        $categories = [];

        foreach (Announcement::published(100) as $item) {
            $categories[$item['category']] = true;
        }

        return view('student/announcements/index', [
            'title' => 'Announcements',
            'pageTitle' => config('navigation.meta.student.announcements.title'),
            'pageSubtitle' => config('navigation.meta.student.announcements.subtitle'),
            'pageKey' => 'announcements',
            'user' => $actor,
            'role' => $actor['role'],
            'announcements' => $announcements,
            'categories' => array_keys($categories),
            'filters' => ['search' => $search, 'category' => $category],
        ]);
    }

    public function show(Request $request, string $publicId)
    {
        $actor = auth_user();
        $announcement = Announcement::findPublished($publicId);

        if ($announcement === null) {
            throw new NotFoundHttpException('The requested announcement could not be found.');
        }

        Announcement::incrementViews((int) $announcement['id']);
        Tracking::record((string) $announcement['title'], 'Announcement', (string) $announcement['public_id']);

        $attachments = Announcement::decodeList($announcement['attachments'] ?? null);

        foreach ($attachments as $index => $attachment) {
            if (!empty($attachment['file_id'])) {
                $attachments[$index]['file'] = File::find((int) $attachment['file_id']);
            }
        }

        return view('student/announcements/show', [
            'title' => $announcement['title'],
            'pageTitle' => $announcement['title'],
            'pageSubtitle' => $announcement['category'] . ' · ' . fmt_date($announcement['published_at']),
            'pageKey' => 'announcements',
            'user' => $actor,
            'role' => $actor['role'],
            'announcement' => $announcement,
            'attachments' => $attachments,
            'related' => array_values(array_filter(
                Announcement::published(8),
                static fn (array $item): bool => (int) $item['id'] !== (int) $announcement['id'],
            )),
        ]);
    }

    public function downloadAttachment(Request $request, string $publicId, string $fileId)
    {
        $announcement = Announcement::findPublished($publicId);
        $file = File::find((int) $fileId);

        if ($announcement === null || $file === null || $file['entity_type'] !== 'announcement' || $file['entity_id'] !== $announcement['public_id']) {
            throw new NotFoundHttpException('The requested announcement attachment could not be found.');
        }

        return FileStorage::download($file);
    }
}
