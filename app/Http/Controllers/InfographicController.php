<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Infographic;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class InfographicController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth_user();
        $infographics = array_map(static function (array $row): array {
            $row['file'] = File::find((int) $row['image_file_id']);

            return $row;
        }, Infographic::all());

        return view('admin/infographics/index', [
            'title' => 'Dashboard Infographics',
            'pageTitle' => config('navigation.meta.admin.infographics.title'),
            'pageSubtitle' => config('navigation.meta.admin.infographics.subtitle'),
            'pageKey' => 'infographics',
            'user' => $actor,
            'role' => $actor['role'],
            'infographics' => $infographics,
            'errors' => session('errors', []),
        ]);
    }

    public function store(Request $request)
    {
        $actor = auth_user();
        $file = $this->uploadedFile($request, 'image');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(['image' => 'Choose a picture to upload.'], $request->input());
        }

        try {
            $stored = FileStorage::storeUpload(
                $file,
                'infographics',
                'infographic',
                'pending',
                (int) $actor['id'],
                5 * 1024 * 1024,
                ['image/jpeg' => 'jpg', 'image/png' => 'png'],
            );
        } catch (RuntimeException $exception) {
            throw new ValidationException(['image' => $exception->getMessage()], $request->input());
        }

        $id = Infographic::create((int) $stored['id'], (int) $actor['id']);
        DB::table('files')->where('id', (int) $stored['id'])->update(['entity_id' => (string) $id]);

        AuditLogger::log(
            'infographic.created',
            'Dashboard Infographic Added',
            (string) $stored['original_name'] . ' was added to the student dashboard rotation.',
            ['entity_type' => 'infographic', 'entity_id' => (string) $id, 'tone' => 'blue', 'icon' => 'image'],
        );
        session()->flash('status', 'Picture added to the student dashboard rotation.');

        return redirect(url('/admin/infographics'));
    }

    public function destroy(Request $request, string $id)
    {
        $infographic = Infographic::find((int) $id);

        if ($infographic === null) {
            throw new NotFoundHttpException('The requested infographic could not be found.');
        }

        $file = File::find((int) $infographic['image_file_id']);
        Infographic::delete((int) $id);

        if ($file !== null) {
            FileStorage::delete($file);
        }

        AuditLogger::log(
            'infographic.deleted',
            'Dashboard Infographic Removed',
            'A picture was removed from the student dashboard rotation.',
            ['entity_type' => 'infographic', 'entity_id' => (string) (int) $id, 'tone' => 'red', 'icon' => 'image'],
        );
        session()->flash('status', 'Picture removed from the student dashboard.');

        return redirect(url('/admin/infographics'));
    }

    public function image(Request $request, string $id)
    {
        $infographic = Infographic::find((int) $id);

        if ($infographic === null) {
            throw new NotFoundHttpException('The requested infographic could not be found.');
        }

        $file = File::find((int) $infographic['image_file_id']);

        if ($file === null) {
            throw new NotFoundHttpException('The requested infographic could not be found.');
        }

        $mime = (string) ($file['mime_type'] ?? '');

        if (!\in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new NotFoundHttpException('The requested infographic could not be found.');
        }

        $path = FileStorage::path($file);
        $content = is_file($path) ? file_get_contents($path) : false;

        if ($content === false) {
            throw new NotFoundHttpException('The requested infographic could not be found.');
        }

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) \strlen($content),
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }
}
