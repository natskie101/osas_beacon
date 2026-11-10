<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;

final class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth_user();
        $requests = DocumentRequest::forStudent((int) $actor['id']);

        return view('student/document-requests/index', [
            'title' => 'Document Requests',
            'pageTitle' => config('navigation.meta.student.document-requests.title'),
            'pageSubtitle' => config('navigation.meta.student.document-requests.subtitle'),
            'pageKey' => 'document-requests',
            'user' => $actor,
            'role' => $actor['role'],
            'requests' => $requests,
            'counts' => $this->counts($requests),
        ]);
    }

    public function create(Request $request)
    {
        return view('student/document-requests/create', [
            'title' => 'New Document Request',
            'pageTitle' => 'New Document Request',
            'pageSubtitle' => 'Choose the document you need and tell OSAS how it will be used.',
            'pageKey' => 'document-requests',
            'user' => auth_user(),
            'role' => auth_user()['role'],
            'values' => array_merge([
                'document_type' => '',
                'purpose' => '',
                'copies' => 1,
                'delivery_mode' => 'Pick-up at OSAS',
            ], old()),
            'errors' => session('errors', []),
        ]);
    }

    public function store(Request $request)
    {
        $actor = auth_user();
        $data = $this->validate($request, [
            'document_type' => 'required|string|max:150',
            'purpose' => 'required|string|max:1000',
            'copies' => 'required|integer|min:1|max:10',
            'delivery_mode' => 'required|in:Pick-up at OSAS,Email PDF copy,Courier arrangement',
        ]);
        $now = date('Y-m-d H:i:s');
        $id = DocumentRequest::create([
            'student_user_id' => (int) $actor['id'],
            'student_name' => trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']),
            'student_id' => (string) ($actor['student_id'] ?? ''),
            'student_email' => (string) $actor['email'],
            'program' => (string) ($actor['program'] ?? ''),
            'year_level' => (string) ($actor['year_level'] ?? ''),
            'document_type' => $data['document_type'],
            'purpose' => $data['purpose'],
            'copies' => (int) $data['copies'],
            'delivery_mode' => $data['delivery_mode'],
            'status' => 'Submitted',
            'priority' => 'Medium',
            'requested_at' => $now,
            'processed_by' => 'OSAS Records',
        ]);
        $documentRequest = DocumentRequest::find($id);
        DocumentRequest::addEvent($id, 'Submitted', trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']), 'Request submitted through the student portal.');

        NotificationService::send((int) $actor['id'], 'Document Request Received', 'Your request for ' . $data['document_type'] . ' has been received and will be reviewed by OSAS.', 'info', 'document_request', (string) $documentRequest['public_id']);

        foreach (DB::table('users')->where('role', 'Coordinator')->where('status', 'Active')->pluck('id') as $staffId) {
            NotificationService::send((int) $staffId, 'New Document Request', $documentRequest['student_name'] . ' requested ' . $data['document_type'] . '.', 'info', 'document_request', (string) $documentRequest['public_id']);
        }

        AuditLogger::log('document_request.created', 'Document Request Submitted', $documentRequest['student_name'] . ' requested ' . $data['document_type'] . '.', ['entity_type' => 'document_request', 'entity_id' => (string) $documentRequest['public_id'], 'tone' => 'blue', 'icon' => 'printer', 'target_user_id' => (int) $actor['id']]);
        session()->flash('status', 'Your document request has been submitted.');

        return redirect(url('/student/document-requests/' . $documentRequest['public_id']));
    }

    public function show(Request $request, string $publicId)
    {
        $actor = auth_user();
        $documentRequest = DocumentRequest::findForStudent($publicId, (int) $actor['id']);

        if ($documentRequest === null) {
            throw new NotFoundHttpException('The requested document request could not be found.');
        }

        return view('student/document-requests/show', [
            'title' => $documentRequest['document_type'],
            'pageTitle' => $documentRequest['document_type'],
            'pageSubtitle' => $documentRequest['public_id'] . ' Â· ' . $documentRequest['status'],
            'pageKey' => 'document-requests',
            'user' => $actor,
            'role' => $actor['role'],
            'requestRecord' => $documentRequest,
            'timeline' => DocumentRequest::timeline((int) $documentRequest['id']),
        ]);
    }

    public function cancel(Request $request, string $publicId)
    {
        $actor = auth_user();
        $documentRequest = DocumentRequest::findForStudent($publicId, (int) $actor['id']);

        if ($documentRequest === null) {
            throw new NotFoundHttpException('The requested document request could not be found.');
        }

        if (!in_array($documentRequest['status'], ['Submitted', 'Under Review'], true)) {
            session()->flash('error', 'This request can no longer be cancelled.');

            return redirect(url('/student/document-requests/' . $documentRequest['public_id']));
        }

        DocumentRequest::update((int) $documentRequest['id'], ['status' => 'Cancelled']);
        DocumentRequest::addEvent((int) $documentRequest['id'], 'Cancelled', trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']), 'Request cancelled by the student.');
        AuditLogger::log('document_request.cancelled', 'Document Request Cancelled', $documentRequest['public_id'] . ' was cancelled by the student.', ['entity_type' => 'document_request', 'entity_id' => (string) $documentRequest['public_id'], 'tone' => 'amber', 'icon' => 'close', 'target_user_id' => (int) $actor['id']]);
        session()->flash('status', 'Document request cancelled.');

        return redirect(url('/student/document-requests/' . $documentRequest['public_id']));
    }

    /** @return array{total:int,active:int,ready:int,released:int} */
    private function counts(array $requests): array
    {
        $result = ['total' => count($requests), 'active' => 0, 'ready' => 0, 'released' => 0];

        foreach ($requests as $request) {
            if (in_array($request['status'], ['Submitted', 'Under Review'], true)) {
                $result['active']++;
            } elseif ($request['status'] === 'Ready for Pick-up') {
                $result['ready']++;
            } elseif ($request['status'] === 'Released') {
                $result['released']++;
            }
        }

        return $result;
    }
}
