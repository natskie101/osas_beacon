@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $requests */
/** @var array<string, int> $counts */
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
    <a class="btn btn--primary" href="{!! url('/student/document-requests/create') !!}">New Request</a>
</div>

<?php if ($requests === []): ?>
    <div class="card panel-card">
        <div class="empty-state">
            <strong>You have not requested any documents yet.</strong>
            <div>Use the New Request button to submit your first request.</div>
            <a class="btn btn--primary" href="{!! url('/student/document-requests/create') !!}">Request a Document</a>
        </div>
    </div>
<?php else: ?>
    <div class="doc-list">
        <?php foreach ($requests as $requestRecord): ?>
            <article class="doc-card">
                <div class="doc-card__body">
                    <div class="doc-card__head">
                        <span class="doc-card__code">REF: {{ $requestRecord['public_id'] }}</span>
                        <span class="badge">{{ $requestRecord['status'] }}</span>
                    </div>
                    <h3 class="doc-card__title"><a href="{!! url('/student/document-requests/' . $requestRecord['public_id']) !!}">{{ $requestRecord['document_type'] }}</a></h3>
                    <p class="doc-card__desc">{!! (int) $requestRecord['copies'] !!} cop{!! (int) $requestRecord['copies'] === 1 ? 'y' : 'ies' !!} · Delivery: {{ $requestRecord['delivery_mode'] }}<?php if (!empty($requestRecord['purpose'])): ?> · Purpose: {{ App\Support\Html::summary($requestRecord['purpose'], 160) }}<?php endif; ?></p>
                    <div class="doc-card__meta">
                        <span>Requested {{ fmt_date($requestRecord['requested_at']) }} ({{ time_ago($requestRecord['requested_at']) }})</span>
                        <?php if (!empty($requestRecord['processed_by'])): ?>
                            <span>·</span>
                            <span>Processed by {{ $requestRecord['processed_by'] }}</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a class="btn sr-view-btn" href="{!! url('/student/document-requests/' . $requestRecord['public_id']) !!}">View Request</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

@endsection
