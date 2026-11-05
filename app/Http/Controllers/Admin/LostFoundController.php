<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
<<<<<<< HEAD
use App\Models\File;
use App\Models\LostFoundClaim;
use App\Models\LostFoundItem;
use App\Services\AuditLogger;
use App\Services\FileStorage;
=======
use App\Models\LostFoundClaim;
use App\Models\LostFoundItem;
use App\Services\AuditLogger;
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
use App\Services\NotificationService;
use App\Support\Auth;
use App\Support\DB;
use App\Support\Exceptions\NotFoundHttpException;
<<<<<<< HEAD
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use RuntimeException;
=======
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37

final class LostFoundController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $filters = [
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'type' => $request->string('type'),
            'category' => $request->string('category'),
        ];
        $pagination = LostFoundItem::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed'],
            'newest',
            ['type' => 'type', 'category' => 'category'],
        );

        return $this->page('layouts.app', 'admin/lost-found/index', [
            'title' => 'Lost & Found',
            'pageTitle' => config('navigation.meta.admin.lost-found.title'),
            'pageSubtitle' => config('navigation.meta.admin.lost-found.subtitle'),
            'pageKey' => 'lost-found',
            'user' => $actor,
            'role' => $actor['role'],
            'items' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => LostFoundItem::counts(),
            'pendingClaims' => LostFoundItem::pendingClaims(8),
<<<<<<< HEAD
            'claimCounts' => LostFoundItem::pendingClaimCounts(),
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'categories' => $this->categories(),
        ]);
    }

<<<<<<< HEAD
    public function export(Request $request): Response
    {
        $rows = DB::select(
            'SELECT public_id, type, title, category, status, location, reported_by, reporter_email,
                    student_id, date_occurred, posted_at
             FROM lost_found_items
             ORDER BY posted_at DESC, id DESC',
        );

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Reference', 'Type', 'Title', 'Category', 'Status', 'Location', 'Reported by', 'Reporter email', 'Student ID', 'Date occurred', 'Posted at']);

        foreach ($rows as $row) {
            fputcsv($stream, [
                $row['public_id'],
                $row['type'],
                $row['title'],
                $row['category'],
                $row['status'],
                $row['location'],
                $row['reported_by'],
                $row['reporter_email'],
                $row['student_id'],
                $row['date_occurred'],
                $row['posted_at'],
            ]);
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return Response::download('lost-found-items.csv', "\xEF\xBB\xBF" . $csv, 'text/csv; charset=UTF-8');
    }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    public function create(Request $request): Response
    {
        return $this->form(Auth::user(), null);
    }

    public function store(Request $request): Response
    {
        $actor = Auth::user();
        $data = $this->validated($request);
<<<<<<< HEAD
        $image = $this->storeImage($request, $actor);

        if ($image !== null) {
            $data['image_file_id'] = $image['file_id'];
        }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        $data['date_occurred'] = db_datetime((string) ($data['date_occurred'] ?? ''));
        $data['posted_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = (int) $actor['id'];
        $id = LostFoundItem::create($data);
        $item = LostFoundItem::find($id);

<<<<<<< HEAD
        if ($image !== null) {
            DB::update('UPDATE files SET entity_id = ? WHERE id = ?', [$item['public_id'], $image['file_id']]);
        }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        AuditLogger::log('lost_found.created', 'Lost & Found Item Posted', $item['title'] . ' (' . $item['public_id'] . ') was posted.', ['entity_type' => 'lost_found', 'entity_id' => (string) $item['public_id'], 'tone' => 'blue', 'icon' => 'search']);
        Session::flash('status', 'Lost & found item posted.');

        return Response::redirect(url('/admin/lost-found/' . $item['public_id']));
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $item = $this->find($publicId);

        return $this->page('layouts.app', 'admin/lost-found/show', [
            'title' => $item['title'],
            'pageTitle' => $item['title'],
            'pageSubtitle' => $item['type'] . ' · ' . $item['public_id'] . ' · ' . $item['status'],
            'pageKey' => 'lost-found',
            'user' => $actor,
            'role' => $actor['role'],
            'item' => $item,
            'claims' => LostFoundItem::claimsFor((int) $item['id']),
<<<<<<< HEAD
            'imageFile' => !empty($item['image_file_id']) ? File::find((int) $item['image_file_id']) : null,
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'audit' => AuditLog::recent(20),
        ]);
    }

    public function edit(Request $request, string $publicId): Response
    {
        return $this->form(Auth::user(), $this->find($publicId));
    }

    public function update(Request $request, string $publicId): Response
    {
        $item = $this->find($publicId);
        $data = $this->validated($request);
<<<<<<< HEAD
        $image = $this->storeImage($request, Auth::user());
        $oldImage = null;

        if ($image !== null) {
            $data['image_file_id'] = $image['file_id'];

            if (!empty($item['image_file_id'])) {
                $oldImage = File::find((int) $item['image_file_id']);
            }
        }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        $data['date_occurred'] = db_datetime((string) ($data['date_occurred'] ?? ''));
        LostFoundItem::update((int) $item['id'], $data);
        $updated = LostFoundItem::find((int) $item['id']) ?? $item;

<<<<<<< HEAD
        if ($image !== null) {
            DB::update('UPDATE files SET entity_id = ? WHERE id = ?', [$updated['public_id'], $image['file_id']]);

            if ($oldImage !== null && (int) $oldImage['id'] !== (int) $image['file_id']) {
                FileStorage::delete($oldImage);
            }
        }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        AuditLogger::log('lost_found.updated', 'Lost & Found Item Updated', $updated['title'] . ' (' . $updated['public_id'] . ') was updated.', ['entity_type' => 'lost_found', 'entity_id' => (string) $updated['public_id'], 'tone' => 'blue', 'icon' => 'edit']);
        Session::flash('status', 'Lost & found item saved.');

        return Response::redirect(url('/admin/lost-found/' . $updated['public_id']));
    }

    public function approveClaim(Request $request, string $publicId, string $claimId): Response
    {
        $item = $this->find($publicId);
        $claim = $this->claim($claimId, (int) $item['id']);
        LostFoundClaim::update((int) $claim['id'], [
            'status' => 'Verified',
            'verified_by' => trim(Auth::user()['first_name'] . ' ' . Auth::user()['last_name']),
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
        LostFoundItem::update((int) $item['id'], ['status' => 'Claim Pending']);

        if (!empty($claim['claimant_user_id'])) {
            NotificationService::send((int) $claim['claimant_user_id'], 'Ownership Claim Verified', 'Your claim for "' . $item['title'] . '" has been verified. Please coordinate with OSAS for release of the item.', 'success', 'lost_found', (string) $item['public_id']);
            AuditLogger::log('lost_found.claim_verified', 'Ownership Claim Verified', 'Claim ' . $claim['public_id'] . ' for ' . $item['title'] . ' was verified.', ['entity_type' => 'lost_found', 'entity_id' => (string) $item['public_id'], 'tone' => 'green', 'icon' => 'checkCircle', 'target_user_id' => (int) $claim['claimant_user_id']]);
        }

        Session::flash('status', 'Ownership claim verified.');

        return Response::redirect(url('/admin/lost-found/' . $item['public_id']));
    }

    public function rejectClaim(Request $request, string $publicId, string $claimId): Response
    {
        $item = $this->find($publicId);
        $claim = $this->claim($claimId, (int) $item['id']);
        LostFoundClaim::update((int) $claim['id'], [
            'status' => 'Rejected',
            'verified_by' => trim(Auth::user()['first_name'] . ' ' . Auth::user()['last_name']),
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        if (!empty($claim['claimant_user_id'])) {
            NotificationService::send((int) $claim['claimant_user_id'], 'Ownership Claim Rejected', 'Your claim for "' . $item['title'] . '" could not be verified. Contact OSAS if you have additional proof.', 'warning', 'lost_found', (string) $item['public_id']);
        }

        AuditLogger::log('lost_found.claim_rejected', 'Ownership Claim Rejected', 'Claim ' . $claim['public_id'] . ' for ' . $item['title'] . ' was rejected.', ['entity_type' => 'lost_found', 'entity_id' => (string) $item['public_id'], 'tone' => 'amber', 'icon' => 'close']);
        Session::flash('status', 'Ownership claim rejected.');

        return Response::redirect(url('/admin/lost-found/' . $item['public_id']));
    }

    public function markReturned(Request $request, string $publicId): Response
    {
        $item = $this->find($publicId);
        LostFoundItem::update((int) $item['id'], ['status' => 'Returned']);
        AuditLogger::log('lost_found.returned', 'Item Marked Returned', $item['title'] . ' was marked as returned.', ['entity_type' => 'lost_found', 'entity_id' => (string) $item['public_id'], 'tone' => 'green', 'icon' => 'checkCircle']);
        Session::flash('status', 'Item marked as returned.');

        return Response::redirect(url('/admin/lost-found/' . $item['public_id']));
    }

    public function archive(Request $request, string $publicId): Response
    {
        $item = $this->find($publicId);
        LostFoundItem::update((int) $item['id'], ['status' => 'Archived']);
        AuditLogger::log('lost_found.archived', 'Lost & Found Item Archived', $item['title'] . ' was archived.', ['entity_type' => 'lost_found', 'entity_id' => (string) $item['public_id'], 'tone' => 'amber', 'icon' => 'fileText']);
        Session::flash('status', 'Item archived.');

        return Response::redirect(url('/admin/lost-found/' . $item['public_id']));
    }

<<<<<<< HEAD
    public function downloadImage(Request $request, string $publicId): Response
    {
        $item = $this->find($publicId);

        if (empty($item['image_file_id'])) {
            throw new NotFoundHttpException('No image is attached to this item.');
        }

        $file = File::find((int) $item['image_file_id']);

        if ($file === null) {
            throw new NotFoundHttpException('No image is attached to this item.');
        }

        return FileStorage::download($file);
    }

    /**
     * @param array<string, mixed> $actor
     * @return array{file_id:int,name:string,size:string}|null
     */
    private function storeImage(Request $request, array $actor): ?array
    {
        $file = $request->file('image');

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
            throw new ValidationException(['image' => $exception->getMessage()], $request->all());
        }

        return [
            'file_id' => (int) $stored['id'],
            'name' => (string) $stored['original_name'],
            'size' => format_bytes((int) $stored['size_bytes']),
        ];
    }

=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    /** @param array<string, mixed> $actor @param array<string, mixed>|null $item */
    private function form(array $actor, ?array $item): Response
    {
        $isEdit = $item !== null;
        $old = Session::getFlash('old', []);
        $values = $isEdit ? array_merge($item, is_array($old) ? $old : []) : array_merge([
            'type' => 'Found',
            'title' => '',
            'category' => '',
            'description' => '',
            'location' => '',
            'date_occurred' => date('Y-m-d\TH:i'),
            'reported_by' => 'OSAS Office',
            'reporter_email' => (string) config('app.institution.support_email'),
            'student_id' => '',
            'contact' => (string) config('app.institution.support_email'),
            'status' => 'Open',
            'custody' => 'OSAS Office',
            'attachment' => '',
        ], is_array($old) ? $old : []);

        if ($isEdit) {
            $values['date_occurred'] = datetime_input($values['date_occurred']);
        }

        return $this->page('layouts.app', 'admin/lost-found/form', [
            'title' => $isEdit ? 'Edit Lost & Found Item' : 'Post Lost & Found Item',
            'pageTitle' => $isEdit ? 'Edit Lost & Found Item' : 'Post Lost & Found Item',
            'pageSubtitle' => $isEdit ? 'Update the item record and custody details for ' . $item['public_id'] . '.' : 'Record a lost or found item received by OSAS.',
            'pageKey' => 'lost-found',
            'user' => $actor,
            'role' => $actor['role'],
            'item' => $item,
            'values' => $values,
            'errors' => Session::errors(),
<<<<<<< HEAD
            'imageFile' => $isEdit && !empty($item['image_file_id']) ? File::find((int) $item['image_file_id']) : null,
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $this->validate($request, [
            'type' => 'required|in:Lost,Found',
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:4000',
            'location' => 'required|string|max:180',
            'date_occurred' => 'nullable|date',
            'reported_by' => 'required|string|max:150',
            'reporter_email' => 'required|email|max:190',
            'student_id' => 'nullable|string|max:30',
            'contact' => 'nullable|string|max:190',
            'status' => 'required|in:Open,Claim Pending,Unclaimed,Returned,Archived,Closed',
            'custody' => 'nullable|string|max:150',
            'attachment' => 'nullable|string|max:255',
        ]);
    }

    /** @return list<string> */
    private function categories(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) $row['category'],
            DB::select('SELECT DISTINCT category FROM lost_found_items WHERE category <> "" ORDER BY category ASC'),
        )));
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $item = LostFoundItem::findByPublicId($publicId);

        if ($item === null) {
            throw new NotFoundHttpException('The requested lost & found item could not be found.');
        }

        return $item;
    }

    /** @return array<string, mixed> */
    private function claim(string $claimId, int $itemId): array
    {
        $claim = LostFoundClaim::findByPublicId($claimId);

        if ($claim === null || (int) $claim['item_id'] !== $itemId) {
            throw new NotFoundHttpException('The requested ownership claim could not be found.');
        }

        return $claim;
    }
}
