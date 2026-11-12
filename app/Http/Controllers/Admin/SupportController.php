<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SupportController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth_user();
        $filters = [
            'search' => (string) $request->string('search'),
            'status' => (string) $request->string('status'),
            'category' => (string) $request->string('category'),
        ];
        $pagination = SupportTicket::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Open', 'In Progress', 'Resolved', 'Closed'],
            'newest',
            ['category' => 'category'],
        );

        return view('admin/support/index', [
            'title' => 'Support & Concerns',
            'pageTitle' => config('navigation.meta.admin.support.title'),
            'pageSubtitle' => config('navigation.meta.admin.support.subtitle'),
            'pageKey' => 'support',
            'user' => $actor,
            'role' => $actor['role'],
            'tickets' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => SupportTicket::counts(),
            'categories' => $this->categories(),
        ]);
    }

    public function show(Request $request, string $publicId)
    {
        $actor = auth_user();
        $ticket = $this->find($publicId);

        return view('admin/support/show', [
            'title' => $ticket['subject'],
            'pageTitle' => $ticket['subject'],
            'pageSubtitle' => $ticket['public_id'] . ' Â· ' . $ticket['student_name'] . ' Â· ' . $ticket['status'],
            'pageKey' => 'support',
            'user' => $actor,
            'role' => $actor['role'],
            'ticket' => $ticket,
            'imageFile' => SupportTicket::imageAttachment((string) $ticket['public_id']),
            'messages' => SupportTicket::messages((int) $ticket['id']),
        ]);
    }

    public function reply(Request $request, string $publicId)
    {
        $actor = auth_user();
        $ticket = $this->find($publicId);
        $data = $this->validate($request, ['body' => 'required|string|max:4000']);
        $author = trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']);
        SupportTicket::addMessage((int) $ticket['id'], $author, 'OSAS Support', $data['body']);

        if (in_array($ticket['status'], ['Open', 'Resolved'], true)) {
            SupportTicket::update((int) $ticket['id'], ['status' => 'In Progress']);
        }

        if (!empty($ticket['student_user_id'])) {
            NotificationService::send((int) $ticket['student_user_id'], 'Support Ticket Reply', 'OSAS Support replied to your ticket "' . $ticket['subject'] . '".', 'info', 'support', (string) $ticket['public_id']);
        }

        AuditLogger::log('support.replied', 'Support Ticket Reply', $author . ' replied to ' . $ticket['public_id'] . '.', ['entity_type' => 'support', 'entity_id' => (string) $ticket['public_id'], 'tone' => 'blue', 'icon' => 'headset', 'target_user_id' => $ticket['student_user_id'] ?? null]);
        session()->flash('status', 'Reply sent to the student.');

        return redirect(url('/admin/support/' . $ticket['public_id']));
    }

    public function updateStatus(Request $request, string $publicId)
    {
        $ticket = $this->find($publicId);
        $data = $this->validate($request, [
            'status' => 'required|in:Open,In Progress,Resolved,Closed',
            'assigned_to' => 'nullable|string|max:150',
        ]);
        SupportTicket::update((int) $ticket['id'], [
            'status' => $data['status'],
            'assigned_to' => trim((string) ($data['assigned_to'] ?? '')),
        ]);

        if (!empty($ticket['student_user_id'])) {
            NotificationService::send((int) $ticket['student_user_id'], 'Support Ticket Updated', 'Your ticket "' . $ticket['subject'] . '" is now marked as ' . $data['status'] . '.', $data['status'] === 'Resolved' ? 'success' : 'info', 'support', (string) $ticket['public_id']);
        }

        AuditLogger::log('support.updated', 'Support Ticket Updated', $ticket['public_id'] . ' was moved to ' . $data['status'] . '.', ['entity_type' => 'support', 'entity_id' => (string) $ticket['public_id'], 'tone' => 'blue', 'icon' => 'headset', 'target_user_id' => $ticket['student_user_id'] ?? null]);
        session()->flash('status', 'Ticket status updated.');

        return redirect(url('/admin/support/' . $ticket['public_id']));
    }

    /** @return list<string> */
    private function categories(): array
    {
        return DB::table('support_tickets')
            ->distinct()
            ->where('category', '<>', '')
            ->orderBy('category')
            ->pluck('category')
            ->map(static fn (mixed $category): string => (string) $category)
            ->all();
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $ticket = SupportTicket::findByPublicId($publicId);

        if ($ticket === null) {
            throw new NotFoundHttpException('The requested support ticket could not be found.');
        }

        return $ticket;
    }
}
