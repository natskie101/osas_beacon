@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $announcements */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
/** @var list<string> $categories */
$queryFor = static function (array $overrides = []) use ($filters): string {
    $query = array_filter(array_merge($filters, $overrides), static fn (mixed $value): bool => $value !== null && $value !== '');
    unset($query['page']);

    return $query === [] ? '' : '?' . http_build_query($query);
};
?><div class="page-head">
    <div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
    <div class="page-head__actions">
        <a href="{!! url('/admin/announcements/create') !!}" class="btn btn--primary">Create Announcement</a>
    </div>
</div>

<div class="stats">
    <div class="stat"><div class="stat__label">Published</div><div class="stat__value">{!! number_format($counts['published'] ?? 0) !!}</div><div class="stat__sub">Visible in student portals</div></div>
    <div class="stat"><div class="stat__label">Scheduled</div><div class="stat__value">{!! number_format($counts['scheduled'] ?? 0) !!}</div><div class="stat__sub">Queued for future release</div></div>
    <div class="stat"><div class="stat__label">Drafts</div><div class="stat__value">{!! number_format($counts['draft'] ?? 0) !!}</div><div class="stat__sub">Not yet published</div></div>
    <div class="stat"><div class="stat__label">Total Views</div><div class="stat__value">{!! number_format($counts['views'] ?? 0) !!}</div><div class="stat__sub">Across all announcements</div></div>
</div>

<form class="filter-bar filter-bar--3" method="GET" action="{!! url('/admin/announcements') !!}">
    <div class="field">
        <label for="search">Search announcements</label>
        <span class="input-group"><span class="input-group__ico">{!! App\Support\Icons::render('search', 15) !!}</span><input class="input" id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title, body, author or reference"></span>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select class="input" id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (['Published', 'Scheduled', 'Draft', 'Archived'] as $status): ?>
                <option value="{{ $status }}"{!! ($filters['status'] ?? '') === $status ? ' selected' : '' !!}>{{ $status }}</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="category">Category</label>
        <select class="input" id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="{{ $category }}"{!! ($filters['category'] ?? '') === $category ? ' selected' : '' !!}>{{ $category }}</option>
            <?php endforeach; ?>
        </select>
    </div>
    
</form>

<div class="table-wrap">
    <div class="table-toolbar">
        <div>
            <div class="panel-title">Announcement Library</div>
            <div class="cell-sub">Showing {!! number_format($pagination['from']) !!}–{!! number_format($pagination['to']) !!} of {!! number_format($pagination['total']) !!} announcements</div>
        </div>
        <a href="{!! url('/admin/announcements/create') !!}" class="btn btn--outline btn--sm">New Notice</a>
    </div>

    <?php if ($announcements === []): ?>
        <div class="empty-state"><strong>No announcements match these filters.</strong><div>Try another status, category or search term.</div></div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data">
                <thead><tr><th>Announcement</th><th>Audience</th><th>Status</th><th>Published</th><th>Views</th><th class="u-right">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($announcements as $announcement): ?>
                    <tr>
                        <td>
                            <div class="identity-cell">
                                <span class="avatar avatar--blue">{!! App\Support\Icons::render('megaphone', 15) !!}</span>
                                <div>
                                    <a class="cell-title" href="{!! url('/admin/announcements/' . $announcement['public_id']) !!}">{{ $announcement['title'] }}</a>
                                    <div class="cell-sub">{{ $announcement['category'] }} · {{ $announcement['public_id'] }}</div>
                                    <div class="cell-sub">{{ $announcement['author'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td><div class="cell-title">{{ $announcement['audience'] }}</div><div class="cell-sub">{{ $announcement['priority'] }}</div></td>
                        <td><span class="badge {{ status_class($announcement['status']) }}">{{ $announcement['status'] }}</span></td>
                        <td><div class="cell-title">{{ fmt_date($announcement['published_at'] ?: $announcement['scheduled_for']) }}</div><div class="cell-sub">{{ $announcement['published_at'] ? fmt_datetime($announcement['published_at']) : ($announcement['scheduled_for'] ? 'Scheduled' : 'Not published') }}</div></td>
                        <td><div class="cell-title">{!! number_format((int) $announcement['views']) !!}</div><div class="cell-sub">portal views</div></td>
                        <td class="u-right">
                            <div class="cell-actions">
                                <a class="btn btn--ghost btn--sm" href="{!! url('/admin/announcements/' . $announcement['public_id']) !!}">Open</a>
                                <a class="btn btn--outline btn--sm" href="{!! url('/admin/announcements/' . $announcement['public_id'] . '/edit') !!}">Edit</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    @include('partials/pagination', [
        'pagination' => $pagination,
        'baseUrl' => '/admin/announcements',
        'query' => $filters,
        'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' announcements',
    ])
</div>

@endsection
