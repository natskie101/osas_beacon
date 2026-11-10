@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $regulation */
/** @var list<array<string, mixed>|string> $sanctions */
?><div class="page-head">
    <div><div class="page-head__eyebrow"><a href="{!! url('/admin/regulations') !!}">Rules & Regulations</a> / {{ $regulation['public_id'] }}</div><h2 class="page-head__title">{{ $regulation['title'] }}</h2><p class="page-head__sub">{{ $regulation['reference_code'] }} · {{ $regulation['category'] }}{!! !empty($regulation['severity']) ? ' · ' . e($regulation['severity']) : '' !!} · {{ $regulation['status'] }}</p></div>
    <div class="page-head__actions"><a class="btn btn--outline" href="{!! url('/admin/regulations/' . $regulation['public_id'] . '/edit') !!}">{!! App\Support\Icons::render('edit', 14) !!} Edit</a><?php if ($regulation['status'] !== 'Published'): ?><form method="POST" action="{!! url('/admin/regulations/' . $regulation['public_id'] . '/publish') !!}">{!! csrf_field() !!}<button class="btn btn--primary" type="submit">Publish</button></form><?php endif; ?><?php if ($regulation['status'] !== 'Archived'): ?><form method="POST" action="{!! url('/admin/regulations/' . $regulation['public_id'] . '/archive') !!}">{!! csrf_field() !!}<button class="btn btn--ghost" type="submit">Archive</button></form><?php endif; ?></div>
</div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card">
            <div class="panel-card__head"><div><div class="panel-title">Policy Content</div><p class="panel-card__sub">Student-facing policy text.</p></div><span class="badge {{ status_class($regulation['status']) }}">{{ $regulation['status'] }}</span></div>
            <?php if (!empty($regulation['summary'])): ?><p class="content-lede">{{ $regulation['summary'] }}</p><?php endif; ?>
            <div class="content-detail">{!! body_html($regulation['body']) !!}</div>
            <?php if ($sanctions !== []): ?><div class="policy-sanctions"><div class="section-title">Sanctions and Penalties</div><ul><?php foreach ($sanctions as $sanction): ?><?php $tier = is_array($sanction) ? (string) ($sanction['tier'] ?? '') : ''; $text = is_array($sanction) ? (string) ($sanction['text'] ?? '') : (string) $sanction; ?><li>{!! $tier !== '' ? '<strong>' . e($tier) . ':</strong> ' : '' !!}{{ $text }}</li><?php endforeach; ?></ul></div><?php endif; ?>
        </section>
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Recent Activity</div></div><a class="link" href="{!! url('/admin/activity') !!}">View all</a></div><div class="mini-list"><?php foreach (array_slice($audit ?? [], 0, 6) as $entry): ?><div class="mini-item"><span class="log-item__ico">{!! App\Support\Icons::render($entry['icon'] ?? 'info', 13) !!}</span><div><strong>{{ $entry['title'] }}</strong><span>{{ $entry['text'] }}</span></div><em>{{ time_ago($entry['created_at']) }}</em></div><?php endforeach; ?></div></section>
    </div>
    <aside class="detail-grid__side">
        <section class="card panel-card"><div class="panel-title">Governance</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono">{{ $regulation['reference_code'] }}</dd></div><div><dt>Category</dt><dd>{{ $regulation['category'] }}</dd></div><div><dt>Severity</dt><dd>{{ $regulation['severity'] ?: 'Not rated' }}</dd></div><div><dt>Audience</dt><dd>{{ $regulation['audience'] }}</dd></div><div><dt>Effective</dt><dd>{{ $regulation['effective_date'] }}</dd></div><div><dt>Review</dt><dd>{{ $regulation['review_schedule'] }}</dd></div><div><dt>Approved by</dt><dd>{{ $regulation['approval_body'] }}</dd></div><div><dt>Views</dt><dd>{!! number_format((int) $regulation['views']) !!}</dd></div><div><dt>Updated</dt><dd>{{ fmt_datetime($regulation['updated_at']) }}</dd></div></dl></section>
    </aside>
</div>

@endsection
