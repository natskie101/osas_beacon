@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $entries */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var list<string> $actions */
/** @var int $total */
?><div class="page-head">
    <div><h2 class="page-head__title">{{ $pageTitle }}</h2><p class="page-head__sub">{{ $pageSubtitle }}</p></div>
    <span class="badge badge--brand">{!! number_format($total) !!} recorded events</span>
</div>

<form class="filter-bar filter-bar--3" method="GET" action="{!! url('/admin/activity') !!}">
    <div class="field"><label for="search">Search activity</label><span class="input-group"><span class="input-group__ico">{!! App\Support\Icons::render('search', 15) !!}</span><input class="input" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Actor, action, title or reference"></span></div>
    <div class="field"><label for="action">Action</label><select class="input" id="action" name="action"><option value="">All actions</option><?php foreach ($actions as $action): ?><option value="{{ $action }}"{!! ($filters['action'] ?? '') === $action ? ' selected' : '' !!}>{{ $action }}</option><?php endforeach; ?></select></div>
    
</form>

<div class="card panel-card">
    <div class="panel-card__head"><div><div class="panel-title">Audit Trail</div><p class="panel-card__sub">Server-recorded account, content and support actions.</p></div><span class="badge badge--info">Immutable log</span></div>
    <?php if ($entries === []): ?>
        <div class="empty-state"><strong>No activity matches these filters.</strong></div>
    <?php else: ?>
        <div class="activity-list">
            <?php foreach ($entries as $entry): ?>
                <article class="activity-item">
                    <span class="log-item__ico activity-item__ico">{!! App\Support\Icons::render($entry['icon'] ?: 'info', 14) !!}</span>
                    <div class="activity-item__body">
                        <div class="activity-item__head"><strong>{{ $entry['title'] }}</strong><time>{{ fmt_datetime($entry['created_at']) }}</time></div>
                        <p>{{ $entry['text'] }}</p>
                        <div class="activity-item__meta">
                            <span>{{ $entry['actor_display'] ?: 'System' }}</span>
                            <span class="mono">{{ $entry['action'] }}</span>
                            <?php if (!empty($entry['target_display'])): ?><span>Target: {{ $entry['target_display'] }}</span><?php endif; ?>
                            <?php if (!empty($entry['entity_id'])): ?><span>Record: {{ $entry['entity_id'] }}</span><?php endif; ?>
                            <span>{{ $entry['ip_address'] }}</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    @include('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/activity', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' activity entries'])
</div>

@endsection
