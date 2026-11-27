@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $regulation */
/** @var list<string> $sanctions */
?>
<div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="{!! url('/student/rules') !!}">Rules and Regulations</a> / {{ $regulation['category'] }}</div>
        <h2 class="page-head__title" style="font-size: 28px;">{{ $regulation['title'] }}</h2>
        <p class="page-head__sub" style="font-size: 14px;">{{ $regulation['reference_code'] }} / {{ $regulation['approval_body'] ?: 'Office of Student Affairs and Services' }} / Effective {{ fmt_date($regulation['effective_date'] ?: $regulation['updated_at']) }}</p>
    </div>
</div>

<article class="card panel-card content-article" style="max-width: 1200px;">
    <div class="content-detail" style="font-size: 15px; line-height: 1.7;">{!! body_html($regulation['body']) !!}</div>
</article>

<?php if ($sanctions !== []): ?>
    <section class="sr-sanctions" style="max-width: 1200px;">
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

<form method="POST" action="{!! url('/student/rules/' . $regulation['public_id'] . '/acknowledge') !!}" class="sr-ack" style="max-width: 1200px;">
    {!! csrf_field() !!}
    <button class="btn sr-ack__btn" type="submit">I ACKNOWLEDGE THIS POLICY</button>
    <div class="sr-ack__note">Acknowledgement is recorded for this session.</div>
</form>

@endsection
