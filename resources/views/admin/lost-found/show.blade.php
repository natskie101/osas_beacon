@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $item */
/** @var list<array<string, mixed>> $claims */
/** @var array<string, mixed>|null $imageFile */
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="{!! url('/admin/lost-found') !!}">Lost & Found</a> / {{ $item['public_id'] }}</div><h2 class="page-head__title">{{ $item['title'] }}</h2><p class="page-head__sub">{{ $item['type'] }} · {{ $item['category'] }} · {{ $item['status'] }}</p></div><div class="page-head__actions"><a class="btn btn--outline" href="{!! url('/admin/lost-found/' . $item['public_id'] . '/edit') !!}">{!! App\Support\Icons::render('edit', 14) !!} Edit</a><?php if ($item['status'] !== 'Returned'): ?><form method="POST" action="{!! url('/admin/lost-found/' . $item['public_id'] . '/returned') !!}">{!! csrf_field() !!}<button class="btn btn--primary" type="submit">Mark Returned</button></form><?php endif; ?><?php if ($item['status'] !== 'Archived'): ?><form method="POST" action="{!! url('/admin/lost-found/' . $item['public_id'] . '/archive') !!}">{!! csrf_field() !!}<button class="btn btn--ghost" type="submit">Archive</button></form><?php endif; ?></div></div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Item Record</div></div><span class="badge {{ status_class($item['status']) }}">{{ $item['status'] }}</span></div><p class="content-lede">{!! nl2br(e($item['description'] ?: 'No description provided.')) !!}</p><?php if ($imageFile !== null): ?><div class="lf-detail-media"><img src="{!! url('/admin/lost-found/' . $item['public_id'] . '/image') !!}" alt="{{ $item['title'] }} photo" loading="lazy"><div class="cell-sub">{{ $imageFile['original_name'] ?? 'Item photo' }} &middot; {{ format_bytes((int) ($imageFile['size_bytes'] ?? 0)) }} · <a class="link" href="{!! url('/admin/lost-found/' . $item['public_id'] . '/image') !!}">Download photo</a></div></div><?php endif; ?><div class="event-facts"><div><span>Type</span><strong>{{ $item['type'] }}</strong></div><div><span>Location</span><strong>{{ $item['location'] }}</strong></div><div><span>Date occurred</span><strong>{{ fmt_datetime($item['date_occurred']) }}</strong></div><div><span>Custody</span><strong>{{ $item['custody'] }}</strong></div><div><span>Reported by</span><strong>{{ $item['reported_by'] }}</strong></div><div><span>Contact</span><strong>{{ $item['contact'] }}</strong></div></div></section>
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Ownership Claims</div><p class="panel-card__sub">Review proof submitted by students.</p></div><span class="badge badge--brand">{!! count($claims) !!} claims</span></div>
            <?php if ($claims === []): ?><div class="empty-state empty-state--compact"><strong>No claims submitted yet.</strong></div><?php else: ?>
                <div class="claim-list">
                    <?php foreach ($claims as $claim): ?>
                        <article class="claim-card">
                            <div class="claim-card__head"><div><strong>{{ $claim['claimant_name'] }}</strong><span>{{ $claim['claimant_email'] }} · {{ $claim['student_id'] }} · {{ fmt_datetime($claim['created_at']) }}</span></div><span class="badge {{ status_class($claim['status']) }}">{{ $claim['status'] }}</span></div>
                            <p><strong>Proof:</strong> {{ $claim['proof'] }}</p>
                            <p>{{ $claim['message'] }}</p>
                            <?php if ($claim['status'] === 'Pending'): ?>
                                <div class="claim-card__actions"><form method="POST" action="{!! url('/admin/lost-found/' . $item['public_id'] . '/claims/' . $claim['public_id'] . '/approve') !!}">{!! csrf_field() !!}<button class="btn btn--primary btn--sm" type="submit">Verify Claim</button></form><form method="POST" action="{!! url('/admin/lost-found/' . $item['public_id'] . '/claims/' . $claim['public_id'] . '/reject') !!}">{!! csrf_field() !!}<button class="btn btn--ghost btn--sm" type="submit">Reject</button></form></div>
                            <?php elseif ($claim['verified_by']): ?><div class="cell-sub">Reviewed by {{ $claim['verified_by'] }} on {{ fmt_datetime($claim['verified_at']) }}</div><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <aside class="detail-grid__side"><section class="card panel-card"><div class="panel-title">Record Details</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono">{{ $item['public_id'] }}</dd></div><div><dt>Category</dt><dd>{{ $item['category'] }}</dd></div><div><dt>Student ID</dt><dd>{{ $item['student_id'] ?: '—' }}</dd></div><div><dt>Attachment</dt><dd>{{ $item['attachment'] ?: 'None' }}</dd></div><div><dt>Photo</dt><dd>{!! $imageFile !== null ? 'Attached' : 'None' !!}</dd></div><div><dt>Posted</dt><dd>{{ fmt_datetime($item['posted_at']) }}</dd></div><div><dt>Updated</dt><dd>{{ fmt_datetime($item['updated_at']) }}</dd></div></dl></section></aside>
</div>

@endsection
