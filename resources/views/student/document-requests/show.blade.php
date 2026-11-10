@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $requestRecord */
/** @var list<array<string, mixed>> $timeline */
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="{!! url('/student/document-requests') !!}">Document Requests</a> / {{ $requestRecord['public_id'] }}</div><h2 class="page-head__title">{{ $requestRecord['document_type'] }}</h2><p class="page-head__sub">{{ $requestRecord['public_id'] }} · Requested {{ fmt_datetime($requestRecord['requested_at']) }}</p></div><span class="badge {{ status_class($requestRecord['status']) }}">{{ $requestRecord['status'] }}</span></div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Request Summary</div></div><span class="badge {{ status_class($requestRecord['status']) }}">{{ $requestRecord['status'] }}</span></div><div class="event-facts"><div><span>Copies</span><strong>{!! (int) $requestRecord['copies'] !!}</strong></div><div><span>Delivery</span><strong>{{ $requestRecord['delivery_mode'] }}</strong></div><div><span>Priority</span><strong>{{ $requestRecord['priority'] }}</strong></div><div><span>Processed by</span><strong>{{ $requestRecord['processed_by'] }}</strong></div></div><div class="detail-note"><strong>Purpose</strong><p>{!! nl2br(e($requestRecord['purpose'])) !!}</p></div><?php if (!empty($requestRecord['remarks'])): ?><div class="detail-note"><strong>Remarks from OSAS</strong><p>{!! nl2br(e($requestRecord['remarks'])) !!}</p></div><?php endif; ?></section>
        <?php if (in_array($requestRecord['status'], ['Submitted', 'Under Review'], true)): ?><section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Cancel Request</div><p class="panel-card__sub">You can cancel the request while it is still being reviewed.</p></div></div><form method="POST" action="{!! url('/student/document-requests/' . $requestRecord['public_id'] . '/cancel') !!}">{!! csrf_field() !!}<button class="btn btn--ghost" type="submit">Cancel This Request</button></form></section><?php endif; ?>
    </div>
    <aside class="detail-grid__side"><section class="card panel-card"><div class="panel-title">Status Timeline</div><div class="timeline"><?php foreach ($timeline as $event): ?><div class="timeline__item"><span class="timeline__dot"></span><div><strong>{{ $event['status'] }}</strong><span>{{ $event['note'] }}</span><em>{{ $event['by_name'] }} · {{ fmt_datetime($event['created_at']) }}</em></div></div><?php endforeach; ?></div></section><section class="card panel-card"><div class="panel-title">Need changes?</div><p class="panel-card__text">If you need to correct the request, contact the OSAS records desk through the support portal and include the reference number.</p><a class="btn btn--outline btn--block" href="{!! url('/student/support') !!}">Contact OSAS</a></section></aside>
</div>

@endsection
