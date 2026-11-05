<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\Auth;
use App\Support\DB;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class SupportController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $tickets = SupportTicket::forStudent((int) $actor['id']);
        $counts = $this->counts($tickets);
        $search = mb_strtolower($request->string('search'));
        $status = $request->string('status');
        $status = in_array($status, ['Open', 'In Progress', 'Resolved'], true) ? $status : '';

        if ($search !== '') {
            $tickets = array_values(array_filter(
                $tickets,
                static fn (array $ticket): bool => str_contains(mb_strtolower((string) $ticket['public_id']), $search)
                    || str_contains(mb_strtolower((string) $ticket['subject']), $search)
                    || str_contains(mb_strtolower((string) $ticket['description']), $search)
                    || str_contains(mb_strtolower((string) $ticket['category']), $search),
            ));
        }

        if ($status !== '') {
            $tickets = array_values(array_filter(
                $tickets,
                static fn (array $ticket): bool => $status === 'Resolved'
                    ? in_array($ticket['status'], ['Resolved', 'Closed'], true)
                    : $ticket['status'] === $status,
            ));
        }

        return $this->page('layouts.app', 'student/support/index', [
            'title' => 'Feedback and Report',
            'pageTitle' => config('navigation.meta.student.support.title'),
            'pageSubtitle' => config('navigation.meta.student.support.subtitle'),
            'pageKey' => 'support',
            'user' => $actor,
            'role' => $actor['role'],
            'tickets' => $tickets,
            'counts' => $counts,
            'filters' => ['search' => $request->string('search'), 'status' => $status],
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->page('layouts.app', 'student/support/create', [
            'title' => 'Submit a Concern',
            'pageTitle' => 'Submit a Concern',
            'pageSubtitle' => 'Tell OSAS what happened and we will follow up through your portal.',
            'pageKey' => 'support',
            'user' => Auth::user(),
            'role' => Auth::user()['role'],
            'values' => array_merge([
                'category' => '',
                'subject' => '',
                'description' => '',
                'priority' => 'Medium',
            ], Session::getFlash('old', [])),
            'errors' => Session::errors(),
        ]);
    }

    public function store(Request $request): Response
    {
        $actor = Auth::user();
        $data = $this->validate($request, [
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:200',
            'description' => 'required|string|max:4000',
            'priority' => 'required|in:Low,Medium,High,Urgent',
        ]);
        $now = date('Y-m-d H:i:s');
        $id = SupportTicket::create([
            'student_user_id' => (int) $actor['id'],
            'student_name' => trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']),
            'student_email' => (string) $actor['email'],
            'student_id' => (string) ($actor['student_id'] ?? ''),
            'program' => (string) ($actor['program'] ?? ''),
            'category' => $data['category'],
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => 'Open',
            'assigned_to' => 'OSAS Support',
            'submitted_at' => $now,
        ]);
        $ticket = SupportTicket::find($id);
        SupportTicket::addMessage($id, (string) $ticket['student_name'], 'Student', $data['description']);
        SupportTicket::addMessage($id, 'OSAS Support System', 'OSAS Support', 'Ticket received by OSAS Support. A staff member will review your concern and reply through this thread.');

        NotificationService::send((int) $actor['id'], 'Support Ticket Received', 'Your ticket "' . $data['subject'] . '" has been received. You can follow the conversation in the portal.', 'info', 'support', (string) $ticket['public_id']);

        foreach (DB::select('SELECT id FROM users WHERE role IN ("Coordinator","Administrator") AND status = "Active"') as $staff) {
            NotificationService::send((int) $staff['id'], 'New Support Ticket', $ticket['student_name'] . ' submitted "' . $data['subject'] . '".', 'warning', 'support', (string) $ticket['public_id']);
        }

        AuditLogger::log('support.created', 'Support Ticket Submitted', $ticket['student_name'] . ' submitted ' . $ticket['public_id'] . '.', ['entity_type' => 'support', 'entity_id' => (string) $ticket['public_id'], 'tone' => 'blue', 'icon' => 'headset', 'target_user_id' => (int) $actor['id']]);
        Session::flash('status', 'Your concern has been submitted.');

        return Response::redirect(url('/student/support/' . $ticket['public_id']));
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $ticket = SupportTicket::findForStudent($publicId, (int) $actor['id']);

        if ($ticket === null) {
            throw new NotFoundHttpException('The requested support ticket could not be found.');
        }

        return $this->page('layouts.app', 'student/support/show', [
            'title' => $ticket['subject'],
            'pageTitle' => $ticket['subject'],
            'pageSubtitle' => $ticket['public_id'] . ' · ' . $ticket['status'],
            'pageKey' => 'support',
            'user' => $actor,
            'role' => $actor['role'],
            'ticket' => $ticket,
            'messages' => SupportTicket::messages((int) $ticket['id']),
            'errors' => Session::errors(),
        ]);
    }

    public function reply(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $ticket = SupportTicket::findForStudent($publicId, (int) $actor['id']);

        if ($ticket === null) {
            throw new NotFoundHttpException('The requested support ticket could not be found.');
        }

        if (in_array($ticket['status'], ['Closed'], true)) {
            Session::flash('error', 'This ticket is closed. Open a new concern if you need further help.');

            return Response::redirect(url('/student/support/' . $ticket['public_id']));
        }

        $data = $this->validate($request, ['body' => 'required|string|max:4000']);
        SupportTicket::addMessage((int) $ticket['id'], trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']), 'Student', $data['body']);
        SupportTicket::update((int) $ticket['id'], ['status' => 'Open']);

        foreach (DB::select('SELECT id FROM users WHERE role IN ("Coordinator","Administrator") AND status = "Active"') as $staff) {
            NotificationService::send((int) $staff['id'], 'Student Replied to Support Ticket', $ticket['student_name'] . ' replied to "' . $ticket['subject'] . '".', 'info', 'support', (string) $ticket['public_id']);
        }

        Session::flash('status', 'Reply added to the ticket.');

        return Response::redirect(url('/student/support/' . $ticket['public_id']));
    }

    public function close(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $ticket = SupportTicket::findForStudent($publicId, (int) $actor['id']);

        if ($ticket === null) {
            throw new NotFoundHttpException('The requested support ticket could not be found.');
        }

        SupportTicket::update((int) $ticket['id'], ['status' => 'Closed']);
        AuditLogger::log('support.closed', 'Support Ticket Closed', $ticket['public_id'] . ' was closed by the student.', ['entity_type' => 'support', 'entity_id' => (string) $ticket['public_id'], 'tone' => 'amber', 'icon' => 'checkCircle', 'target_user_id' => (int) $actor['id']]);
        Session::flash('status', 'Ticket closed. Thank you for your feedback.');

        return Response::redirect(url('/student/support/' . $ticket['public_id']));
    }

    public function rate(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $ticket = SupportTicket::findForStudent($publicId, (int) $actor['id']);

        if ($ticket === null) {
            throw new NotFoundHttpException('The requested support ticket could not be found.');
        }

        if (!in_array($ticket['status'], ['Resolved', 'Closed'], true) || $ticket['rating'] !== null) {
            Session::flash('error', 'This ticket is not available for rating.');

            return Response::redirect(url('/student/support/' . $ticket['public_id']));
        }

        $data = $this->validate($request, ['rating' => 'required|integer|min:1|max:5']);
        SupportTicket::update((int) $ticket['id'], ['rating' => (int) $data['rating']]);
        Session::flash('status', 'Thank you for rating the support you received.');

        return Response::redirect(url('/student/support/' . $ticket['public_id']));
    }

    /** @return array{total:int,open:int,in_progress:int,resolved:int} */
    private function counts(array $tickets): array
    {
        $result = ['total' => count($tickets), 'open' => 0, 'in_progress' => 0, 'resolved' => 0];

        foreach ($tickets as $ticket) {
            if ($ticket['status'] === 'Open') {
                $result['open']++;
            } elseif ($ticket['status'] === 'In Progress') {
                $result['in_progress']++;
            } elseif (in_array($ticket['status'], ['Resolved', 'Closed'], true)) {
                $result['resolved']++;
            }
        }

        return $result;
    }
}
