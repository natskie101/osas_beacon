@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $user */
/** @var array{total:int,students:int,active:int,pending:int,inactive:int,staff:int} $counts */
/** @var list<array<string, mixed>> $recent */
/** @var array{support_pending:int,support_open:int,support_in_progress:int,documents_pending:int,documents_submitted:int,documents_review:int,documents_ready:int,lost_found_pending:int,lost_found_claims:int,events_upcoming:int,announcements_published:int} $operations */
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
    <div class="page-head__actions">
        <a href="{!! url('/admin/reports') !!}" class="btn btn--outline">Open Reports</a>
        <a href="{!! url('/admin/dashboard') !!}" class="btn btn--primary">
            {!! App\Support\Icons::render('refresh', 14) !!} Refresh Data
        </a>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <div class="stat__label">Portal Accounts</div>
        <div class="stat__value">{!! number_format($counts['total']) !!}</div>
        <div class="stat__sub">{!! number_format($counts['students']) !!} students &middot; {!! number_format($counts['staff']) !!} staff</div>
    </div>
    <div class="stat">
        <div class="stat__label">Active Accounts</div>
        <div class="stat__value">{!! number_format($counts['active']) !!}</div>
        <div class="stat__sub">{!! number_format($counts['inactive']) !!} deactivated</div>
    </div>
    <div class="stat">
        <div class="stat__label">Pending Verification</div>
        <div class="stat__value">{!! number_format($counts['pending']) !!}</div>
        <div class="stat__sub">Awaiting OSAS review</div>
    </div>
</div>

<div class="dash-grid">
    <section class="card panel-card">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">Live System Audit Log</div>
                <p class="panel-card__sub">The latest security and account events recorded by the server.</p>
            </div>
            <?php if (in_array($user['role'] ?? '', ['Coordinator', 'Administrator'], true)): ?>
                <a href="{!! url('/admin/activity') !!}" class="link">View full log</a>
            <?php endif; ?>
        </div>
        <?php if ($recent === []): ?>
            <div class="empty-state">No activity has been recorded yet.</div>
        <?php else: ?>
            <div class="log-list log-list--card">
                <?php foreach ($recent as $entry): ?>
                    <?php
                    $toneClass = match ($entry['tone'] ?? 'brand') {
                        'green', 'success' => 'log-item__ico--green',
                        'amber', 'warning' => 'log-item__ico--amber',
                        'blue', 'info' => 'log-item__ico--blue',
                        'red', 'danger' => 'log-item__ico--red',
                        default => '',
                    };
                    ?>
                    <div class="log-item">
                        <span class="log-item__ico {!! $toneClass !!}">{!! App\Support\Icons::render($entry['icon'] ?? 'info', 13) !!}</span>
                        <div class="log-item__body">
                            <div class="log-item__title">{{ $entry['title'] }}</div>
                            <div class="log-item__text">{{ $entry['text'] }}</div>
                            <div class="log-item__time">
                                {!! App\Support\Icons::render('clock', 11) !!}
                                {{ time_ago($entry['created_at']) }} &middot; {{ fmt_datetime($entry['created_at']) }}
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card panel-card">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">Operational Queue</div>
                <p class="panel-card__sub">Live records that still need OSAS attention.</p>
            </div>
            <span class="badge badge--brand">Live data</span>
        </div>
        <div class="log-list log-list--card">
            <div class="log-item">
                <span class="log-item__ico log-item__ico--amber">{!! App\Support\Icons::render('headset', 13) !!}</span>
                <div class="log-item__body">
                    <div class="log-item__title"><a class="link" href="{!! url('/admin/support') !!}">Support &amp; Concerns</a></div>
                    <div class="log-item__text">{!! number_format($operations['support_pending']) !!} unresolved &middot; {!! number_format($operations['support_open']) !!} open, {!! number_format($operations['support_in_progress']) !!} in progress</div>
                </div>
            </div>
            <div class="log-item">
                <span class="log-item__ico log-item__ico--blue">{!! App\Support\Icons::render('printer', 13) !!}</span>
                <div class="log-item__body">
                    <div class="log-item__title"><a class="link" href="{!! url('/admin/document-requests') !!}">Document Requests</a></div>
                    <div class="log-item__text">{!! number_format($operations['documents_pending']) !!} in process &middot; {!! number_format($operations['documents_ready']) !!} ready for pick-up</div>
                </div>
            </div>
            <div class="log-item">
                <span class="log-item__ico">{!! App\Support\Icons::render('search', 13) !!}</span>
                <div class="log-item__body">
                    <div class="log-item__title"><a class="link" href="{!! url('/admin/lost-found') !!}">Lost &amp; Found</a></div>
                    <div class="log-item__text">{!! number_format($operations['lost_found_pending']) !!} open records &middot; {!! number_format($operations['lost_found_claims']) !!} claims pending verification</div>
                </div>
            </div>
            <div class="log-item">
                <span class="log-item__ico log-item__ico--green">{!! App\Support\Icons::render('calendar', 13) !!}</span>
                <div class="log-item__body">
                    <div class="log-item__title"><a class="link" href="{!! url('/admin/events') !!}">Events &amp; Activities</a></div>
                    <div class="log-item__text">{!! number_format($operations['events_upcoming']) !!} upcoming activities on the calendar</div>
                </div>
            </div>
            <div class="log-item">
                <span class="log-item__ico log-item__ico--blue">{!! App\Support\Icons::render('megaphone', 13) !!}</span>
                <div class="log-item__body">
                    <div class="log-item__title"><a class="link" href="{!! url('/admin/announcements') !!}">Announcements</a></div>
                    <div class="log-item__text">{!! number_format($operations['announcements_published']) !!} notices published to student portals</div>
                </div>
            </div>
        </div>
    </section>
</div>

@endsection
