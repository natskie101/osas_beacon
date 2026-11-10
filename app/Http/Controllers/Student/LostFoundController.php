<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\LostFoundClaim;
use App\Models\LostFoundItem;
use App\Models\Tracking;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Services\NotificationService;
use App\Support\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use Illuminate\Http\Request;
use App\Support\Session;
use RuntimeException;

final class LostFoundController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        $items = LostFoundItem::visible();
        $type = (string) $request->string('type');
        $status = (string) $request->string('status');
        $category = (string) $request->string('category');
        $search = mb_strtolower((string) $request->string('search'));

        if (in_array($type, ['Lost', 'Found'], true)) {
            $items = array_values(array_filter($items, static fn (array $item): bool => $item['type'] === $type));
        }

        if ($status !== '') {
            $items = array_values(array_filter($items, static fn (array $item): bool => $item['status'] === $status));
        }

        if ($category !== '') {
            $items = array_values(array_filter($items, static fn (array $item): bool => (string) $item['category'] === $category));
        }

        if ($search !== '') {
            $items = array_values(array_filter(
                $items,
                static fn (array $item): bool => str_contains(mb_strtolower((string) $item['title']), $search)
                    || str_contains(mb_strtolower((string) $item['description']), $search)
                    || str_contains(mb_strtolower((string) $item['location']), $search),
            ));
        }

        return view('student/lost-found/index', [
            'title' => 'Lost and Found',
            'pageTitle' => config('navigation.meta.student.lost-found.title'),
            'pageSubtitle' => config('navigation.meta.student.lost-found.subtitle'),
            'pageKey' => 'lost-found',
            'user' => $actor,
            'role' => $actor['role'],
            'items' => $items,
            'myReports' => LostFoundItem::forReporter((string) $actor['email'], (string) ($actor['student_id'] ?? '')),
            'myClaims' => LostFoundItem::claimsByUser((int) $actor['id']),
            'filters' => ['search' => (string) $request->string('search'), 'type' => $type, 'status' => $status, 'category' => $category],
            'counts' => LostFoundItem::counts(),
        ]);
    }

    public function downloadImage(Request $request, string $publicId)
    {
        $item = LostFoundItem::findVisible($publicId);

        if ($item === null || empty($item['image_file_id'])) {
            throw new NotFoundHttpException('The requested item image could not be found.');
        }

        $file = File::find((int) $item['image_file_id']);

        if ($file === null) {
            throw new NotFoundHttpException('The requested item image could not be found.');
        }

        $mime = (string) ($file['mime_type'] ?? '');

        if (!\in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new NotFoundHttpException('The requested item image could not be found.');
        }

        $path = FileStorage::path($file);
        $content = is_file($path) ? file_get_contents($path) : false;

        if ($content === false) {
            throw new NotFoundHttpException('The requested item image could not be found.');
        }

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) \strlen($content),
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function report(Request $request)
    {
        return view('student/lost-found/report', [
            'title' => 'Report a Lost or Found Item',
            'pageTitle' => 'Report a Lost or Found Item',
            'pageSubtitle' => 'Submit the details so OSAS can match the item with its owner or custodian.',
            'pageKey' => 'lost-found',
            'user' => Auth::user(),
            'role' => Auth::user()['role'],
            'values' => array_merge([
                'type' => 'Lost',
                'title' => '',
                'category' => '',
                'description' => '',
                'location' => '',
                'date_occurred' => date('Y-m-d\TH:i'),
                'contact' => Auth::user()['email'],
            ], Session::getFlash('old', [])),
            'errors' => Session::errors(),
        ]);
    }

    public function storeReport(Request $request)
    {
        $actor = Auth::user();
        $data = $this->validate($request, [
            'type' => 'required|in:Lost,Found',
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:100',
            'description' => 'required|string|max:2000',
            'location' => 'required|string|max:180',
            'date_occurred' => 'required|date',
            'contact' => 'required|string|max:190',
        ]);
        $image = $this->storeImage($request, $actor);

        if ($image !== null) {
            $data['image_file_id'] = $image['file_id'];
        }

        $data['date_occurred'] = db_datetime((string) $data['date_occurred']);
        $data['reported_by'] = trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']);
        $data['reporter_email'] = (string) $actor['email'];
        $data['student_id'] = (string) ($actor['student_id'] ?? '');
        $data['status'] = 'Open';
        $data['custody'] = 'Reported by student';
        $data['posted_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = (int) $actor['id'];
        $id = LostFoundItem::create($data);
        $item = LostFoundItem::find($id);

        if ($image !== null) {
            DB::table('files')->where('id', (int) $image['file_id'])->update(['entity_id' => (string) $item['public_id']]);
        }

        AuditLogger::log('lost_found.reported', 'Lost & Found Report Submitted', $item['title'] . ' was reported by ' . $data['reported_by'] . '.', ['entity_type' => 'lost_found', 'entity_id' => (string) $item['public_id'], 'tone' => 'blue', 'icon' => 'search', 'target_user_id' => (int) $actor['id']]);
        NotificationService::send((int) $actor['id'], 'Lost & Found Report Received', 'Your report for "' . $item['title'] . '" was submitted. OSAS will review it.', 'info', 'lost_found', (string) $item['public_id']);

        foreach (DB::table('users')->where('role', 'Coordinator')->where('status', 'Active')->pluck('id') as $staffId) {
            NotificationService::send((int) $staffId, 'New Lost & Found Report', $item['type'] . ' item "' . $item['title'] . '" was reported by ' . $data['reported_by'] . '.', 'info', 'lost_found', (string) $item['public_id']);
        }

        Session::flash('status', 'Your report has been submitted.');

        return redirect(url('/student/lost-found/' . $item['public_id']));
    }

    /**
     * @param array<string, mixed> $actor
     * @return array{file_id:int}|null
     */
    private function storeImage(Request $request, array $actor): ?array
    {
        $file = $this->uploadedFile($request, 'image');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        try {
            $stored = FileStorage::storeUpload(
                $file,
                'lost-found/pending',
                'lost_found_image',
                'pending',
                (int) $actor['id'],
                5 * 1024 * 1024,
                ['image/jpeg' => 'jpg', 'image/png' => 'png'],
            );
        } catch (RuntimeException $exception) {
            throw new ValidationException(['image' => $exception->getMessage()], $request->input());
        }

        return ['file_id' => (int) $stored['id']];
    }

    public function show(Request $request, string $publicId)
    {
        $actor = Auth::user();
        $item = LostFoundItem::findVisible($publicId);

        if ($item === null) {
            throw new NotFoundHttpException('The requested lost & found item could not be found.');
        }

        Tracking::record((string) $item['title'], 'Lost & Found', (string) $item['public_id']);
        $myClaim = DB::table('lost_found_claims')
            ->where('item_id', (int) $item['id'])
            ->where('claimant_user_id', (int) $actor['id'])
            ->orderByDesc('id')
            ->first();
        $myClaim = $myClaim === null ? null : (array) $myClaim;
        $ownReport = (string) $item['reporter_email'] === (string) $actor['email'];

        return view('student/lost-found/show', [
            'title' => $item['title'],
            'pageTitle' => $item['title'],
            'pageSubtitle' => $item['type'] . ' · ' . $item['category'] . ' · ' . $item['status'],
            'pageKey' => 'lost-found',
            'user' => $actor,
            'role' => $actor['role'],
            'item' => $item,
            'myClaim' => $myClaim,
            'ownReport' => $ownReport,
            'claims' => $ownReport ? LostFoundItem::claimsFor((int) $item['id']) : [],
            'imageFile' => !empty($item['image_file_id']) ? File::find((int) $item['image_file_id']) : null,
            'errors' => Session::errors(),
        ]);
    }

    public function claim(Request $request, string $publicId)
    {
        $actor = Auth::user();
        $item = LostFoundItem::findVisible($publicId);

        if ($item === null) {
            throw new NotFoundHttpException('The requested lost & found item could not be found.');
        }

        if ((string) $item['reporter_email'] === (string) $actor['email']) {
            Session::flash('error', 'You cannot submit a claim for your own report.');

            return redirect(url('/student/lost-found/' . $item['public_id']));
        }

        if (!in_array($item['status'], ['Open', 'Unclaimed', 'Claim Pending'], true)) {
            Session::flash('error', 'This item is no longer accepting claims.');

            return redirect(url('/student/lost-found/' . $item['public_id']));
        }

        $existing = DB::table('lost_found_claims')
            ->where('item_id', (int) $item['id'])
            ->where('claimant_user_id', (int) $actor['id'])
            ->first();

        if ($existing !== null) {
            Session::flash('error', 'You already submitted a claim for this item.');

            return redirect(url('/student/lost-found/' . $item['public_id']));
        }

        $data = $this->validate($request, [
            'proof' => 'required|string|max:1000',
            'message' => 'required|string|max:2000',
        ]);

        $claimId = LostFoundClaim::create([
            'item_id' => (int) $item['id'],
            'claimant_user_id' => (int) $actor['id'],
            'claimant_name' => trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']),
            'claimant_email' => (string) $actor['email'],
            'student_id' => (string) ($actor['student_id'] ?? ''),
            'proof' => $data['proof'],
            'message' => $data['message'],
            'status' => 'Pending',
        ]);
        $claim = LostFoundClaim::find($claimId);

        if ($item['type'] === 'Found') {
            LostFoundItem::update((int) $item['id'], ['status' => 'Claim Pending']);
        }

        AuditLogger::log('lost_found.claim_submitted', 'Ownership Claim Submitted', $claim['claimant_name'] . ' submitted a claim for ' . $item['title'] . '.', ['entity_type' => 'lost_found', 'entity_id' => (string) $item['public_id'], 'tone' => 'amber', 'icon' => 'search', 'target_user_id' => (int) $actor['id']]);
        NotificationService::send((int) $actor['id'], 'Claim Submitted', 'Your claim for "' . $item['title'] . '" is awaiting OSAS verification.', 'info', 'lost_found', (string) $item['public_id']);

        foreach (DB::table('users')->where('role', 'Coordinator')->where('status', 'Active')->pluck('id') as $staffId) {
            NotificationService::send((int) $staffId, 'New Ownership Claim', $claim['claimant_name'] . ' filed a claim for "' . $item['title'] . '".', 'warning', 'lost_found', (string) $item['public_id']);
        }

        Session::flash('status', 'Your claim has been submitted for verification.');

        return redirect(url('/student/lost-found/' . $item['public_id']));
    }
}
