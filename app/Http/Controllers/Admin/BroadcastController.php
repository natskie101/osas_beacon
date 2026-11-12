<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Services\AudienceService;
use App\Services\AuditLogger;
use App\Services\BroadcastService;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;

final class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth_user();
        $filters = [
            'search' => (string) $request->string('search'),
            'status' => (string) $request->string('status'),
        ];
        $pagination = Broadcast::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Draft', 'Pending', 'Scheduled', 'Sent'],
            'newest',
        );

        return view('admin/broadcasts/index', [
            'title' => 'Broadcasts',
            'pageTitle' => config('navigation.meta.admin.broadcasts.title'),
            'pageSubtitle' => config('navigation.meta.admin.broadcasts.subtitle'),
            'pageKey' => 'broadcasts',
            'user' => $actor,
            'role' => $actor['role'],
            'broadcasts' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => Broadcast::counts(),
        ]);
    }

    public function create(Request $request)
    {
        return $this->form(auth_user(), null);
    }

    public function store(Request $request)
    {
        $actor = auth_user();
        $data = $this->validated($request);
        $data['created_by'] = (int) $actor['id'];
        $data['scheduled_at'] = !empty($data['scheduled_at']) ? db_datetime((string) $data['scheduled_at']) : null;
        $data['sent_at'] = null;
        $data['reach'] = 0;
        $data['delivered'] = 0;
        $data['opened'] = 0;
        $id = Broadcast::create($data);
        $broadcast = Broadcast::find($id);

        AuditLogger::log('broadcast.created', 'Broadcast Created', $broadcast['subject'] . ' (' . $broadcast['public_id'] . ') was created.', ['entity_type' => 'broadcast', 'entity_id' => (string) $broadcast['public_id'], 'tone' => 'blue', 'icon' => 'broadcast']);
        session()->flash('status', 'Broadcast saved.');

        return redirect(url('/admin/broadcasts/' . $broadcast['public_id']));
    }

    public function show(Request $request, string $publicId)
    {
        $actor = auth_user();
        $broadcast = $this->find($publicId);

        return view('admin/broadcasts/show', [
            'title' => $broadcast['subject'],
            'pageTitle' => $broadcast['subject'],
            'pageSubtitle' => $broadcast['public_id'] . ' Â· ' . $broadcast['status'] . ' Â· ' . $broadcast['channel'],
            'pageKey' => 'broadcasts',
            'user' => $actor,
            'role' => $actor['role'],
            'broadcast' => $broadcast,
            'recipients' => AudienceService::recipients((string) $broadcast['audience']),
        ]);
    }

    public function edit(Request $request, string $publicId)
    {
        return $this->form(auth_user(), $this->find($publicId));
    }

    public function update(Request $request, string $publicId)
    {
        $broadcast = $this->find($publicId);
        $data = $this->validated($request);
        $data['scheduled_at'] = !empty($data['scheduled_at']) ? db_datetime((string) $data['scheduled_at']) : null;
        Broadcast::update((int) $broadcast['id'], $data);
        $updated = Broadcast::find((int) $broadcast['id']) ?? $broadcast;

        AuditLogger::log('broadcast.updated', 'Broadcast Updated', $updated['subject'] . ' (' . $updated['public_id'] . ') was updated.', ['entity_type' => 'broadcast', 'entity_id' => (string) $updated['public_id'], 'tone' => 'blue', 'icon' => 'edit']);
        session()->flash('status', 'Broadcast saved.');

        return redirect(url('/admin/broadcasts/' . $updated['public_id']));
    }

    public function send(Request $request, string $publicId)
    {
        $actor = auth_user();
        $broadcast = $this->find($publicId);

        if ($broadcast['status'] === 'Sent') {
            session()->flash('error', 'This broadcast has already been sent.');

            return redirect(url('/admin/broadcasts/' . $broadcast['public_id']));
        }

        $delivery = BroadcastService::send($broadcast);
        Broadcast::update((int) $broadcast['id'], [
            'status' => 'Sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'reach' => $delivery['recipients'],
            'delivered' => $delivery['delivered'],
        ]);

        AuditLogger::log('broadcast.sent', 'Broadcast Sent', $broadcast['subject'] . ' was delivered to ' . $delivery['delivered'] . ' recipients.', ['entity_type' => 'broadcast', 'entity_id' => (string) $broadcast['public_id'], 'tone' => 'green', 'icon' => 'send']);
        session()->flash('status', 'Broadcast sent to ' . $delivery['delivered'] . ' recipients.');

        return redirect(url('/admin/broadcasts/' . $broadcast['public_id']));
    }

    /** @param array<string, mixed> $actor @param array<string, mixed>|null $broadcast */
    private function form(array $actor, ?array $broadcast)
    {
        $isEdit = $broadcast !== null;
        $old = old();
        $values = $isEdit ? array_merge($broadcast, is_array($old) ? $old : []) : array_merge([
            'subject' => '',
            'message' => '',
            'channel' => 'Portal + Email',
            'status' => 'Draft',
            'audience' => 'All Student Portals',
            'scheduled_at' => '',
        ], is_array($old) ? $old : []);

        if ($isEdit) {
            $values['scheduled_at'] = datetime_input($values['scheduled_at']);
        }

        return view('admin/broadcasts/form', [
            'title' => $isEdit ? 'Edit Broadcast' : 'Compose Broadcast',
            'pageTitle' => $isEdit ? 'Edit Broadcast' : 'Compose Broadcast',
            'pageSubtitle' => $isEdit ? 'Update the audience and delivery settings for ' . $broadcast['public_id'] . '.' : 'Send a portal notification, email, or both to a student audience.',
            'pageKey' => 'broadcasts',
            'user' => $actor,
            'role' => $actor['role'],
            'broadcast' => $broadcast,
            'values' => $values,
            'errors' => session('errors', []),
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $this->validate($request, [
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:4000',
            'channel' => 'required|in:Portal Notification,Portal + Email,University Email,SMS Alert',
            'status' => 'required|in:Draft,Pending,Scheduled,Sent',
            'audience' => 'required|in:All Student Portals,Incoming Freshmen,Student Officers,Faculty & Staff,Graduating Students',
            'scheduled_at' => 'nullable|date',
        ]);
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $broadcast = Broadcast::findByPublicId($publicId);

        if ($broadcast === null) {
            throw new NotFoundHttpException('The requested broadcast could not be found.');
        }

        return $broadcast;
    }
}
