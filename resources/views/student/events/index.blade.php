@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $events */
/** @var array<string, string> $filters */
/** @var array{all:int,upcoming:int,ongoing:int,completed:int} $eventCounts */
$currentStatus = (string) ($filters['status'] ?? '');
$statusUrl = static function (string $status) use ($filters): string {
    $query = array_filter([
        'search' => $filters['search'] ?? '',
        'status' => $status,
    ], static fn (string $value): bool => $value !== '');

    return url('/student/events' . ($query === [] ? '' : '?' . http_build_query($query)));
};
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
</div>

<form class="filter-bar ev-search" method="GET" action="{!! url('/student/events') !!}">
    <div class="field">
        <span class="input-group"><span class="input-group__ico">{!! App\Support\Icons::render('search', 15) !!}</span><input class="input" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search title, venue, or code..."></span>
    </div>
    <?php if ($currentStatus !== ''): ?><input type="hidden" name="status" value="{{ $currentStatus }}"><?php endif; ?>
</form>

<nav class="ev-tabs" aria-label="Filter events by status">
    <a class="ev-tab{!! $currentStatus === '' ? ' ev-tab--on' : '' !!}" href="{!! $statusUrl('') !!}"{!! $currentStatus === '' ? ' aria-current="page"' : '' !!}>All Events ({!! number_format($eventCounts['all']) !!})</a>
    <a class="ev-tab{!! $currentStatus === 'Upcoming' ? ' ev-tab--on' : '' !!}" href="{!! $statusUrl('Upcoming') !!}"{!! $currentStatus === 'Upcoming' ? ' aria-current="page"' : '' !!}>Upcoming</a>
    <a class="ev-tab{!! $currentStatus === 'Ongoing' ? ' ev-tab--on' : '' !!}" href="{!! $statusUrl('Ongoing') !!}"{!! $currentStatus === 'Ongoing' ? ' aria-current="page"' : '' !!}>Ongoing</a>
    <a class="ev-tab{!! $currentStatus === 'Completed' ? ' ev-tab--on' : '' !!}" href="{!! $statusUrl('Completed') !!}"{!! $currentStatus === 'Completed' ? ' aria-current="page"' : '' !!}>Completed</a>
</nav>

<?php if ($events === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No events found.</strong></div></div>
<?php else: ?>
    <div class="ev-list">
        <?php foreach ($events as $event): ?>
            <?php
            $start = strtotime((string) $event['start_at']);
            $end = strtotime((string) $event['end_at']);
            ?>
            <article class="ev-card">
                <div class="ev-card__head">
                    <span class="ev-card__code">{{ $event['public_id'] }}</span>
                    <span class="badge {!! $event['status'] === 'Scheduled' ? 'badge--pending' : 'badge--published' !!}">{!! $event['status'] === 'Scheduled' ? 'Upcoming' : e((string) $event['status']) !!}</span>
                </div>
                <div class="ev-card__body">
                    <h3 class="ev-card__title"><a href="{!! url('/student/events/' . $event['public_id']) !!}">{{ $event['title'] }}</a></h3>
                    <p class="ev-card__desc">{{ App\Support\Html::summary($event['description'], 200) }}</p>
                    <div class="ev-card__meta">
                        <span>{!! App\Support\Icons::render('calendar', 12) !!} {{ fmt_date($event['start_at']) }} · {{ date('g:i A', $start) }} - {{ date('g:i A', $end) }}</span>
                        <span>{!! App\Support\Icons::render('pin', 12) !!} {{ $event['venue'] }} · {{ $event['mode'] }}</span>
                    </div>
                </div>
                <a class="btn btn--outline btn--sm ev-card__action" href="{!! url('/student/events/' . $event['public_id']) !!}">View Details</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

@endsection
