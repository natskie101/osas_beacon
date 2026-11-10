<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DocumentRequest;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;
use App\Support\Session;

final class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        $filters = [
            'search' => (string) $request->string('search'),
            'status' => (string) $request->string('status'),
            'document_type' => (string) $request->string('document_type'),
            'delivery_mode' => (string) $request->string('delivery_mode'),
        ];
        $pagination = DocumentRequest::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            DocumentRequest::statuses(),
            'newest',
            [
                'document_type' => 'document_type',
                'delivery_mode' => 'delivery_mode',
            ],
        );

        return view('admin/document-requests/index', [
            'title' => 'Document Requests',
            'pageTitle' => config('navigation.meta.admin.document-requests.title'),
            'pageSubtitle' => config('navigation.meta.admin.document-requests.subtitle'),
            'pageKey' => 'document-requests',
            'user' => $actor,
            'role' => $actor['role'],
            'requests' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => DocumentRequest::counts(),
            'filterOptions' => DocumentRequest::filterOptions(),
        ]);
    }

    public function show(Request $request, string $publicId)
    {
        $actor = Auth::user();
        $documentRequest = $this->find($publicId);

        return view('admin/document-requests/show', [
            'title' => $documentRequest['document_type'],
            'pageTitle' => $documentRequest['document_type'],
            'pageSubtitle' => $documentRequest['public_id'] . ' · ' . $documentRequest['student_name'] . ' · ' . $documentRequest['status'],
            'pageKey' => 'document-requests',
            'user' => $actor,
            'role' => $actor['role'],
            'requestRecord' => $documentRequest,
            'timeline' => DocumentRequest::timeline((int) $documentRequest['id']),
            'audit' => AuditLog::recent(20),
        ]);
    }

    public function updateStatus(Request $request, string $publicId)
    {
        $actor = Auth::user();
        $documentRequest = $this->find($publicId);
        $data = $this->validate($request, [
            'status' => 'required|in:Submitted,Under Review,Ready for Pick-up,Released,Rejected,Cancelled',
            'processed_by' => 'required|string|max:150',
            'remarks' => 'nullable|string|max:2000',
        ]);
        $oldStatus = (string) $documentRequest['status'];
        $newStatus = (string) $data['status'];
        $fields = [
            'status' => $newStatus,
            'processed_by' => $data['processed_by'],
            'remarks' => trim((string) ($data['remarks'] ?? '')),
        ];

        if ($newStatus === 'Released') {
            $fields['released_at'] = date('Y-m-d H:i:s');
        }

        DocumentRequest::update((int) $documentRequest['id'], $fields);
        DocumentRequest::addEvent((int) $documentRequest['id'], $newStatus, trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']), trim((string) ($data['remarks'] ?? '')) ?: 'Status changed from ' . $oldStatus . ' to ' . $newStatus . '.');
        $updated = DocumentRequest::find((int) $documentRequest['id']) ?? $documentRequest;

        if (!empty($updated['student_user_id'])) {
            NotificationService::send((int) $updated['student_user_id'], 'Document Request Updated', 'Your request for ' . $updated['document_type'] . ' is now marked as ' . $newStatus . '.', $newStatus === 'Released' ? 'success' : 'info', 'document_request', (string) $updated['public_id']);
        }

        AuditLogger::log('document_request.updated', 'Document Request Updated', $updated['public_id'] . ' was moved from ' . $oldStatus . ' to ' . $newStatus . '.', ['entity_type' => 'document_request', 'entity_id' => (string) $updated['public_id'], 'tone' => 'blue', 'icon' => 'printer', 'target_user_id' => $updated['student_user_id'] ?? null]);
        Session::flash('status', 'Document request updated.');

        return redirect(url('/admin/document-requests/' . $updated['public_id']));
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $request = DocumentRequest::findByPublicId($publicId);

        if ($request === null) {
            throw new NotFoundHttpException('The requested document request could not be found.');
        }

        return $request;
    }
}
