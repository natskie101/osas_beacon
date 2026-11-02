<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LostFoundClaim;
use App\Models\LostFoundItem;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\Auth;
use App\Support\DB;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

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
            'categories' => $this->categories(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(Auth::user(), null);
    }

    public function store(Request $request): Response
    {
        $actor = Auth::user();
        $data = $this->validated($request);
        $data['date_occurred'] = db_datetime((string) ($data['date_occurred'] ?? ''));
        $data['posted_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = (int) $actor['id'];
        $id = LostFoundItem::create($data);
        $item = LostFoundItem::find($id);

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
        $data['date_occurred'] = db_datetime((string) ($data['date_occurred'] ?? ''));
        LostFoundItem::update((int) $item['id'], $data);
        $updated = LostFoundItem::find((int) $item['id']) ?? $item;

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
