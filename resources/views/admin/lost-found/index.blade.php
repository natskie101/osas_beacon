@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $items */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
/** @var array<int, int> $claimCounts */
/** @var list<string> $categories */
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">Lost &amp; Found Management</h2>
        <p class="page-head__sub">Review reported lost and found items, verify ownership claims, and record items returned to students.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--outline" href="{!! url('/admin/lost-found/create') !!}">Post Item</a>
    </div>
</div>

<div class="stats lf-stats">
    <div class="stat">
        <div class="stat__label">Open Reports</div>
        <div class="stat__value">{!! number_format($counts['open'] ?? 0) !!}</div>
        <div class="stat__sub"><span class="badge badge--info">{!! number_format($counts['total'] ?? 0) !!} total items</span> {!! number_format($counts['lost'] ?? 0) !!} lost &middot; {!! number_format($counts['found'] ?? 0) !!} found</div>
    </div>
    <div class="stat">
        <div class="stat__label">Claim Pending</div>
        <div class="stat__value">{!! number_format($counts['claim_pending'] ?? 0) !!}</div>
        <div class="stat__sub"><span class="badge badge--pending">Claim to verify</span> Ownership checks at the OSAS office</div>
    </div>
    <div class="stat">
        <div class="stat__label">Returned to Owner</div>
        <div class="stat__value">{!! number_format($counts['returned'] ?? 0) !!}</div>
        <div class="stat__sub"><span class="badge badge--verified">Verified hand-over</span> Recorded with claimant and date</div>
    </div>
    <div class="stat">
        <div class="stat__label">Unclaimed</div>
        <div class="stat__value">{!! number_format($counts['unclaimed'] ?? 0) !!}</div>
        <div class="stat__sub"><span class="badge badge--draft">For disposal review</span> {!! number_format($counts['archived'] ?? 0) !!} archived reports</div>
    </div>
</div>

<form class="filter-bar lf-filters" method="GET" action="{!! url('/admin/lost-found') !!}">
    <div class="field">
        <label for="search">Search</label>
        <span class="input-group"><span class="input-group__ico">{!! App\Support\Icons::render('search', 15) !!}</span><input class="input" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search code, item, reporter..."></span>
    </div>
    <div class="field">
        <label for="type">Report type</label>
        <select class="input" id="type" name="type">
            <option value="">All report types</option>
            <?php foreach (['Lost', 'Found'] as $type): ?><option value="{{ $type }}"{!! ($filters['type'] ?? '') === $type ? ' selected' : '' !!}>{{ $type }}</option><?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select class="input" id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (['Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed'] as $status): ?><option value="{{ $status }}"{!! ($filters['status'] ?? '') === $status ? ' selected' : '' !!}>{{ $status }}</option><?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="category">Category</label>
        <select class="input" id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?><option value="{{ $category }}"{!! ($filters['category'] ?? '') === $category ? ' selected' : '' !!}>{{ $category }}</option><?php endforeach; ?>
        </select>
    </div>
</form>

<div class="table-toolbar lf-toolbar">
    <div><div class="panel-title">Reported Items Directory</div><div class="cell-sub">Claims are verified at the OSAS office before an item is released.</div></div>
    <span class="badge badge--brand">{!! number_format($pagination['total']) !!} records</span>
</div>

<?php if ($items === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No items match these filters.</strong></div></div>
<?php else: ?>
    <div class="lf-card-grid">
        <?php foreach ($items as $item): ?>
            <?php
            $status = (string) $item['status'];
            $tone = match ($status) {
                'Claim Pending' => 'red',
                'Returned' => 'green',
                'Lost' => 'amber',
                'Unclaimed' => 'amber',
                'Archived', 'Closed' => 'gray',
                default => $item['type'] === 'Lost' ? 'amber' : 'blue',
            };
            $statusLabel = match ($status) {
                'Claim Pending' => 'Claim pending',
                'Returned' => 'Claimed',
                default => $item['type'],
            };
            $letter = mb_strtoupper(mb_substr((string) ($item['category'] ?: $item['title']), 0, 1));
            $claimCount = (int) ($claimCounts[(int) $item['id']] ?? 0);
            ?>
            <article class="lf-card lf-card--{{ $tone }}{!! !empty($item['image_file_id']) ? ' lf-card--has-image' : '' !!}" data-lf-card>
                <div class="lf-card__inner">
                    <div class="lf-card__front">
                        <?php if (!empty($item['image_file_id'])): ?>
                            <img class="lf-card__background-image" src="{!! url('/admin/lost-found/' . $item['public_id'] . '/image') !!}" alt="" loading="lazy">
                        <?php endif; ?>
                        <div class="lf-card__hero">
                            <?php if (!empty($item['image_file_id'])): ?>
                                <button type="button" class="lf-card__flip" data-lf-flip aria-label="Show item photo">{!! App\Support\Icons::render('eye', 15) !!}</button>
                            <?php endif; ?>
                            <span class="lf-card__letter">{{ $letter }}</span>
                            <span class="lf-card__status">{{ $statusLabel }}</span>
                        </div>
                        <div class="lf-card__body">
                            <div class="lf-card__meta">{{ $item['public_id'] }} &middot; {{ mb_strtoupper((string) $item['category']) }}</div>
                            <a class="lf-card__title" href="{!! url('/admin/lost-found/' . $item['public_id']) !!}">{{ $item['title'] }}</a>
                            <ul class="lf-card__facts">
                                <li>{!! App\Support\Icons::render('pin', 12) !!} {{ $item['location'] ?: 'Location not recorded' }}</li>
                                <li>{!! App\Support\Icons::render('calendar', 12) !!} {{ fmt_date($item['date_occurred'] ?: $item['posted_at']) }}</li>
                                <li>{!! App\Support\Icons::render('user', 12) !!} {{ $item['reported_by'] }}</li>
                            </ul>
                            <div class="lf-card__actions">
                                <a class="btn btn--outline btn--sm" href="{!! url('/admin/lost-found/' . $item['public_id']) !!}">View</a>
                                <?php if ($claimCount > 0): ?>
                                    <a class="btn btn--primary btn--sm" href="{!! url('/admin/lost-found/' . $item['public_id']) !!}">Verify ({!! number_format($claimCount) !!})</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="lf-card__back">
                        <?php if (!empty($item['image_file_id'])): ?>
                            <img src="{!! url('/admin/lost-found/' . $item['public_id'] . '/image') !!}" alt="{{ $item['title'] }} photo" loading="lazy">
                        <?php else: ?>
                            <div class="lf-card__no-image">{!! App\Support\Icons::render('eye', 24) !!}<span>No photo attached</span></div>
                        <?php endif; ?>
                        <button type="button" class="btn btn--ghost btn--sm" data-lf-flip>Back</button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

@include('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/lost-found', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' items'])

@endsection
