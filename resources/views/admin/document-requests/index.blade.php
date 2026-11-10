@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $requests */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
/** @var array{document_types:list<string>,delivery_modes:list<string>} $filterOptions */
$statuses = App\Models\DocumentRequest::statuses();
$tabUrl = static function (string $status) use ($filters): string {
    $query = array_filter($filters, static fn (string $value): bool => $value !== '');
    unset($query['page']);

    if ($status === '') {
        unset($query['status']);
    } else {
        $query['status'] = $status;
    }

    $queryString = http_build_query($query);

    return url('/admin/document-requests') . ($queryString === '' ? '' : '?' . $queryString);
};
?><div class="page-head"><div><h2 class="page-head__title">{{ $pageTitle }}</h2><p class="page-head__sub">{{ $pageSubtitle }}</p></div></div>
<div class="stats">
    <div class="stat"><div class="stat__label">Total Requests</div><div class="stat__value">{!! number_format($counts['total'] ?? 0) !!}</div><div class="stat__sub">All document requests</div></div>
    <div class="stat"><div class="stat__label">Submitted</div><div class="stat__value">{!! number_format($counts['submitted'] ?? 0) !!}</div><div class="stat__sub">Awaiting review</div></div>
    <div class="stat"><div class="stat__label">In Progress</div><div class="stat__value">{!! number_format(($counts['review'] ?? 0) + ($counts['ready'] ?? 0)) !!}</div><div class="stat__sub">Under review or ready</div></div>
    <div class="stat"><div class="stat__label">Released</div><div class="stat__value">{!! number_format($counts['released'] ?? 0) !!}</div><div class="stat__sub">Completed requests</div></div>
</div>
<form class="filter-bar request-filters" method="GET" action="{!! url('/admin/document-requests') !!}">
    <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
    <div class="field"><label for="search">Search requests</label><span class="input-group"><span class="input-group__ico">{!! App\Support\Icons::render('search', 15) !!}</span><input class="input" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Student, document or reference"></span></div>
    <div class="field"><label for="document_type">Document Type</label><select class="input" id="document_type" name="document_type"><option value="">All document types</option><?php foreach ($filterOptions['document_types'] as $documentType): ?><option value="{{ $documentType }}"{!! ($filters['document_type'] ?? '') === $documentType ? ' selected' : '' !!}>{{ $documentType }}</option><?php endforeach; ?></select></div>
    <div class="field"><label for="delivery_mode">Delivery Mode</label><select class="input" id="delivery_mode" name="delivery_mode"><option value="">All delivery modes</option><?php foreach ($filterOptions['delivery_modes'] as $deliveryMode): ?><option value="{{ $deliveryMode }}"{!! ($filters['delivery_mode'] ?? '') === $deliveryMode ? ' selected' : '' !!}>{{ $deliveryMode }}</option><?php endforeach; ?></select></div>
</form>
<nav class="request-tabs" aria-label="Filter requests by status">
    <a class="tab{!! ($filters['status'] ?? '') === '' ? ' tab--on' : '' !!}" href="{{ $tabUrl('') }}"{!! ($filters['status'] ?? '') === '' ? ' aria-current="page"' : '' !!}>All Requests <span class="tab__count">{!! number_format($counts['total'] ?? 0) !!}</span></a>
    <?php foreach ($statuses as $status): ?>
        <?php $countKey = match ($status) { 'Submitted' => 'submitted', 'Under Review' => 'review', 'Ready for Pick-up' => 'ready', 'Released' => 'released', 'Rejected' => 'rejected', 'Cancelled' => 'cancelled' }; ?>
        <a class="tab{!! ($filters['status'] ?? '') === $status ? ' tab--on' : '' !!}" href="{{ $tabUrl($status) }}"{!! ($filters['status'] ?? '') === $status ? ' aria-current="page"' : '' !!}>{{ $status }} <span class="tab__count">{!! number_format($counts[$countKey] ?? 0) !!}</span></a>
    <?php endforeach; ?>
</nav>
<div class="table-wrap request-queue">
    <div class="table-toolbar"><div><div class="panel-title">Request Queue</div><div class="cell-sub">Showing {!! number_format($pagination['from']) !!}–{!! number_format($pagination['to']) !!} of {!! number_format($pagination['total']) !!} requests</div></div><span class="badge badge--brand">{!! number_format($pagination['total']) !!} requests</span></div>
    <?php if ($requests === []): ?><div class="empty-state"><strong>No document requests match these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Student</th><th>Document Type</th><th>Date Requested</th><th>Status</th><th>Delivery Mode</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($requests as $requestRecord): ?>
            <?php $nameParts = preg_split('/\s+/', trim((string) $requestRecord['student_name'])) ?: []; ?>
            <tr><td><div class="identity-cell"><span class="avatar avatar--blue">{{ initials($nameParts[0] ?? '', $nameParts[count($nameParts) - 1] ?? '') }}</span><div><a class="cell-title" href="{!! url('/admin/document-requests/' . $requestRecord['public_id']) !!}">{{ $requestRecord['student_name'] }}</a><div class="cell-sub">{{ $requestRecord['student_id'] }} · {{ $requestRecord['program'] }}</div><div class="cell-sub">{{ $requestRecord['student_email'] }}</div></div></div></td><td><div class="cell-title">{{ $requestRecord['document_type'] }}</div><div class="cell-sub mono">{{ $requestRecord['public_id'] }}</div></td><td><div class="cell-title">{{ fmt_date($requestRecord['requested_at']) }}</div><div class="cell-sub">{{ time_ago($requestRecord['requested_at']) }}</div></td><td><span class="badge {{ status_class($requestRecord['status']) }}">{{ $requestRecord['status'] }}</span></td><td>{{ $requestRecord['delivery_mode'] }}<div class="cell-sub">{!! (int) $requestRecord['copies'] !!} cop{!! (int) $requestRecord['copies'] === 1 ? 'y' : 'ies' !!}</div></td><td class="u-right request-actions"><a class="btn btn--outline btn--sm" href="{!! url('/admin/document-requests/' . $requestRecord['public_id']) !!}">Process</a><a class="btn btn--ghost btn--sm" href="{!! url('/admin/document-requests/' . $requestRecord['public_id']) !!}">View</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    @include('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/document-requests', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' requests'])
</div>

@endsection
