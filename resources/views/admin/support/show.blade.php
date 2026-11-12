@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $ticket */
/** @var list<array<string, mixed>> $messages */
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="{!! url('/admin/support') !!}">Support & Concerns</a> / {{ $ticket['public_id'] }}</div><h2 class="page-head__title">{{ $ticket['subject'] }}</h2><p class="page-head__sub">{{ $ticket['student_name'] }} · {{ $ticket['category'] }} · {{ $ticket['status'] }}</p></div><span class="badge {{ status_class($ticket['status']) }}">{{ $ticket['status'] }}</span></div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Conversation</div><p class="panel-card__sub">Replies are visible to the student in their portal.</p></div><span class="badge {{ status_class($ticket['priority']) }}">{{ $ticket['priority'] }} priority</span></div>
            <?php if ($imageFile !== null): ?>
                <div class="support-attachment">
                    <div class="support-attachment__heading">
                        <strong>Photo attached by student</strong>
                        <a class="link" href="{!! url('/admin/support/' . $ticket['public_id'] . '/attachment/' . $imageFile['id']) !!}" target="_blank" rel="noopener">Open full size</a>
                    </div>
                    <a href="{!! url('/admin/support/' . $ticket['public_id'] . '/attachment/' . $imageFile['id']) !!}" target="_blank" rel="noopener">
                        <img src="{!! url('/admin/support/' . $ticket['public_id'] . '/attachment/' . $imageFile['id']) !!}" alt="Photo attached to this concern">
                    </a>
                    <span>{{ $imageFile['original_name'] }}</span>
                </div>
            <?php endif; ?>
            <div class="message-thread"><?php foreach ($messages as $message): ?><article class="message {!! $message['role'] !== 'Student' ? 'message--staff' : '' !!}"><div class="message__head"><strong>{{ $message['author_name'] }}</strong><span>{{ $message['role'] }} · {{ fmt_datetime($message['created_at']) }}</span></div><p>{!! nl2br(e($message['body'])) !!}</p></article><?php endforeach; ?></div></section>
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Reply to Student</div></div>{!! App\Support\Icons::render('send', 20) !!}</div><form method="POST" action="{!! url('/admin/support/' . $ticket['public_id'] . '/reply') !!}" class="form-grid">{!! csrf_field() !!}<div class="field"><label for="body">Message</label><textarea class="input" id="body" name="body" rows="4" required placeholder="Write a clear response or next step."></textarea></div><button class="btn btn--primary" type="submit">Send Reply</button></form></section>
    </div>
    <aside class="detail-grid__side">
        <section class="card panel-card"><div class="panel-title">Ticket Management</div><form method="POST" action="{!! url('/admin/support/' . $ticket['public_id'] . '/status') !!}" class="form-grid">{!! csrf_field() !!}<div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><?php foreach (['Open', 'In Progress', 'Resolved', 'Closed'] as $status): ?><option value="{{ $status }}"{!! $ticket['status'] === $status ? ' selected' : '' !!}>{{ $status }}</option><?php endforeach; ?></select></div><div class="field"><label for="assigned_to">Assigned to</label><input class="input" id="assigned_to" name="assigned_to" value="{{ $ticket['assigned_to'] }}"></div><button class="btn btn--outline" type="submit" data-confirm="Save these ticket updates?">Update Ticket</button></form></section>
        <section class="card panel-card"><div class="panel-title">Student & Record</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono">{{ $ticket['public_id'] }}</dd></div><div><dt>Name</dt><dd>{{ $ticket['student_name'] }}</dd></div><div><dt>Student ID</dt><dd>{{ $ticket['student_id'] }}</dd></div><div><dt>Program</dt><dd>{{ $ticket['program'] }}</dd></div><div><dt>Email</dt><dd>{{ $ticket['student_email'] }}</dd></div><div><dt>Category</dt><dd>{{ $ticket['category'] }}</dd></div><div><dt>Assigned</dt><dd>{{ $ticket['assigned_to'] }}</dd></div><div><dt>Rating</dt><dd>{!! $ticket['rating'] ? (int) $ticket['rating'] . '/5' : 'Not rated' !!}</dd></div><div><dt>Submitted</dt><dd>{{ fmt_datetime($ticket['submitted_at']) }}</dd></div></dl></section>
    </aside>
</div>

@endsection
