@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $regulation */
/** @var list<string> $sanctions */
?>
<div class="sr-detail">
    <section class="sr-detail__hero">
        <div class="sr-detail__hero-category">{{ mb_strtoupper((string) $regulation['category']) }}</div>
        <h1 class="sr-detail__hero-title">{{ $regulation['title'] }}</h1>
        <div class="sr-detail__hero-sub">{{ $regulation['approval_body'] ?: 'Office of Student Affairs and Services' }}</div>
    </section>

    <div class="sr-detail__ref">
        <span class="sr-code">{{ $regulation['reference_code'] }}</span>
        <span class="sr-detail__ref-note">Official student policy</span>
        <span class="sr-badge">In force</span>
    </div>

    <h2 class="sr-detail__title">{{ $regulation['title'] }}</h2>

    <div class="sr-facts">
        <div><span>{!! App\Support\Icons::render('scale', 11) !!} Category</span><strong>{{ $regulation['category'] }}</strong></div>
        <div><span>{!! App\Support\Icons::render('calendar', 11) !!} Last Revised</span><strong>{{ fmt_date($regulation['updated_at']) }}{!! $regulation['approval_body'] ? '<br><small>By: ' . e($regulation['approval_body']) . '</small>' : '' !!}</strong></div>
        <div><span>{!! App\Support\Icons::render('clock', 11) !!} Effective Date</span><strong>{{ $regulation['effective_date'] ?: 'Not set' }}</strong></div>
    </div>

    <section class="card panel-card sr-policy-card">
        <article class="sr-detail__body">
            {!! body_html($regulation['body']) !!}
        </article>
    </section>

    <?php if ($sanctions !== []): ?>
        <section class="sr-sanctions">
            <?php foreach ($sanctions as $index => $sanction): ?>
                <?php
                $tone = match ($index) { 0 => 'red', 1 => 'amber', default => 'gray' };
                $tier = is_array($sanction) ? (string) ($sanction['tier'] ?? '') : '';
                $text = is_array($sanction) ? (string) ($sanction['text'] ?? '') : (string) $sanction;
                $fallback = $index === 0 ? 'First Offense' : ($index === 1 ? 'Second Offense' : ($index === 2 ? 'Third Offense' : 'Offense ' . ($index + 1)));
                ?>
                <div class="sr-sanction sr-sanction--{!! $tone !!}">
                    <strong>{{ $tier !== '' ? $tier : $fallback }}:</strong>
                    <span>{{ $text }}</span>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <form method="POST" action="{!! url('/student/rules/' . $regulation['public_id'] . '/acknowledge') !!}" class="sr-ack">
        {!! csrf_field() !!}
        <button class="btn sr-ack__btn" type="submit">I ACKNOWLEDGE THIS POLICY</button>
        <div class="sr-ack__note">Acknowledgement is recorded for this session.</div>
    </form>
</div>

@endsection
