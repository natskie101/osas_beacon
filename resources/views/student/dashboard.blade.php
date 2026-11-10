@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $announcements */
/** @var array<string, mixed>|null $featuredModule */
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$program = $user['program'] ?: ($user['department'] ?: $user['role']);
$latest = $announcements[0] ?? null;
?>
<div class="sd-dashboard">
    <section class="sd-hero">
        <div class="sd-hero__identity">
            <div class="sd-hero__eyebrow">Welcome back</div>
            <div class="sd-hero__name">{{ $fullName }}</div>
            <div class="sd-hero__program">{{ $program }}{!! !empty($user['year_level']) ? ' &middot; ' . e($user['year_level']) : '' !!}</div>
        </div>
        <div class="sd-hero__facts">
            <div>
                <span>Student ID</span>
                <strong>{{ $user['student_id'] ?: 'Not assigned' }}</strong>
            </div>
            <div>
                <span>Status</span>
                <strong class="sd-status">{{ strtoupper($user['status']) }}</strong>
            </div>
        </div>
    </section>

    <div class="sd-grid">
        <div class="sd-main">
            <section class="sd-card sd-announcement">
                <div class="sd-label">Announcement</div>
                <?php if ($latest !== null): ?>
                    <h3><a href="{!! url('/student/announcements/' . $latest['public_id']) !!}">{{ $latest['title'] }}</a></h3>
                    <p>{{ $latest['summary'] ?: 'Open the announcement to read the full details.' }}</p>
                <?php else: ?>
                    <h3>No announcements yet</h3>
                    <p>Official OSAS updates will appear here once published.</p>
                <?php endif; ?>
            </section>

            <section class="sd-poster">
                <img src="{!! asset('img/choose-health-poster.svg') !!}" alt="Choose health, not smoke" loading="lazy">
            </section>
        </div>

        <aside class="sd-side">
            <section class="sd-card sd-side-card">
                <div class="sd-label">Orientation</div>
                <?php if ($featuredModule !== null): ?>
                    <h3>{{ $featuredModule['title'] }}</h3>
                    <p>Please read the Orientation Module for the instructions.</p>
                    <a class="sd-link" href="{!! url('/student/orientation/' . $featuredModule['public_id']) !!}">Instructions <span>&rarr;</span></a>
                <?php else: ?>
                    <h3>Orientation modules</h3>
                    <p>Your orientation modules will appear here once published.</p>
                    <a class="sd-link" href="{!! url('/student/orientation') !!}">Open Orientation <span>&rarr;</span></a>
                <?php endif; ?>
            </section>

            <section class="sd-card sd-side-card">
                <div class="sd-label">Submit Feedback/Report</div>
                <p>Please check Feedback/Report module</p>
                <a class="sd-link" href="{!! url('/student/support') !!}">Open Feedback/Report <span>&rarr;</span></a>
            </section>
        </aside>
    </div>
</div>

@endsection
