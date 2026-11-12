@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $tickets */
/** @var array<string, int> $counts */
/** @var array<string, string> $filters */
$currentStatus = (string) ($filters['status'] ?? '');
$statusUrl = static function (string $status) use ($filters): string {
    $query = array_filter([
        'search' => $filters['search'] ?? '',
        'status' => $status,
    ], static fn (string $value): bool => $value !== '');

    return url('/student/support' . ($query === [] ? '' : '?' . http_build_query($query)));
};
?><div class="page-head">
    <div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
    <a class="btn btn--primary" href="{!! url('/student/support/create') !!}">Create New Ticket</a>
</div>

<form class="filter-bar support-search" method="GET" action="{!! url('/student/support') !!}">
    <div class="field">
        <span class="input-group"><span class="input-group__ico">{!! App\Support\Icons::render('search', 15) !!}</span><input class="input" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by ticket ID, subject, or keyword..."></span>
    </div>
    <?php if ($currentStatus !== ''): ?><input type="hidden" name="status" value="{{ $currentStatus }}"><?php endif; ?>
</form>

<nav class="support-tabs" aria-label="Filter support tickets by status">
    <a class="support-tab{!! $currentStatus === '' ? ' support-tab--on' : '' !!}" href="{!! $statusUrl('') !!}"{!! $currentStatus === '' ? ' aria-current="page"' : '' !!}>Support Tickets</a>
    <a class="support-tab{!! $currentStatus === 'Open' ? ' support-tab--on' : '' !!}" href="{!! $statusUrl('Open') !!}"{!! $currentStatus === 'Open' ? ' aria-current="page"' : '' !!}>Open</a>
    <a class="support-tab{!! $currentStatus === 'In Progress' ? ' support-tab--on' : '' !!}" href="{!! $statusUrl('In Progress') !!}"{!! $currentStatus === 'In Progress' ? ' aria-current="page"' : '' !!}>In Progress</a>
    <a class="support-tab{!! $currentStatus === 'Resolved' ? ' support-tab--on' : '' !!}" href="{!! $statusUrl('Resolved') !!}"{!! $currentStatus === 'Resolved' ? ' aria-current="page"' : '' !!}>Resolved</a>
</nav>

<?php if ($tickets === []): ?>
    <div class="card panel-card">
        <div class="empty-state">
            <strong>{!! ($filters['search'] ?? '') !== '' || $currentStatus !== '' ? 'No tickets match your search or status.' : 'You have not submitted any concerns yet.' !!}</strong>
            <div>{!! ($filters['search'] ?? '') !== '' || $currentStatus !== '' ? 'Try another keyword or status filter.' : 'Use the support desk whenever you need help from OSAS.' !!}</div>
            <a class="btn btn--primary" href="{!! url('/student/support/create') !!}">Submit a Concern</a>
        </div>
    </div>
<?php else: ?>
    <div class="feed-list support-list">
        <?php foreach ($tickets as $ticket): ?>
            <article class="feed-item support-item">
                <div class="feed-item__body">
                    <div class="support-item__head">
                        <span class="support-item__code">{{ $ticket['public_id'] }}</span>
                        <span class="badge {{ status_class($ticket['status']) }}">{{ (string) $ticket['status'] }}</span>
                    </div>
                    <h3 class="feed-item__title"><a href="{!! url('/student/support/' . $ticket['public_id']) !!}">{{ $ticket['subject'] }}</a></h3>
                    <p class="support-item__category">{{ $ticket['category'] }}</p>
                    <div class="feed-item__meta">
                        <span>{!! App\Support\Icons::render('calendar', 12) !!} {{ fmt_date($ticket['submitted_at']) }}</span>
                        <span>{{ $ticket['student_name'] }}</span>
                        <?php if ($ticket['rating'] !== null): ?>
                            <?php $rating = max(1, min(5, (int) $ticket['rating'])); ?>
                            <span class="support-item__rating" aria-label="Rated {!! $rating !!} out of 5">
                                <span class="support-item__stars">{!! str_repeat('★', $rating) !!}<span>{!! str_repeat('★', 5 - $rating) !!}</span></span>
                                <strong>{!! $rating !!}/5</strong>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <a class="btn btn--primary btn--sm support-item__action" href="{!! url('/student/support/' . $ticket['public_id']) !!}">View</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

@endsection
