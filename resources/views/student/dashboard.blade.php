@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $announcements */
/** @var int|null $announcementCount */
/** @var list<array<string, mixed>> $infographics */
/** @var array<string, mixed>|null $featuredModule */
/** @var array{completed:int,in_progress:int,not_started:int,total:int,percent:float} $orientation */
/** @var int|null $activeTickets */
/** @var int|null $activeRequests */
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$program = $user['program'] ?: ($user['department'] ?: $user['role']);
$latest = $announcements[0] ?? null;
?>
<div class="sd-dashboard">
    <section class="sd-hero">
        <div class="sd-hero__identity">
            <div class="sd-hero__name">{{ $fullName }}</div>
            <div class="sd-hero__program">{{ $program }}{!! !empty($user['year_level']) ? ' / ' . e($user['year_level']) : '' !!}</div>
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
                <?php if ($latest !== null): ?>
                    <h3><a href="{!! url('/student/announcements/' . $latest['public_id']) !!}">{{ $latest['title'] }}</a></h3>
                    <p>{{ $latest['summary'] ?: 'Open the announcement to read the full details.' }}</p>
                    <?php if (count($announcements) > 1): ?>
                        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line-2);">
                            <div style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 8px;">Recent Updates</div>
                            <ul style="display: flex; flex-direction: column; gap: 8px;">
                                <?php foreach (array_slice($announcements, 1, 2) as $ann): ?>
                                    <li style="font-size: 13px; display: flex; align-items: baseline; justify-content: space-between; gap: 10px;">
                                        <a href="{!! url('/student/announcements/' . $ann['public_id']) !!}" style="color: var(--ink); text-decoration: none; font-weight: 600;">&bull; {{ $ann['title'] }}</a>
                                        <span style="font-size: 11px; color: var(--muted-2); white-space: nowrap;">{{ fmt_date($ann['published_at'] ?? $ann['created_at']) }}</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <a class="sd-link" href="{!! url('/student/announcements') !!}">View All Announcements ({!! (int) ($announcementCount ?? count($announcements)) !!}) <span>&rarr;</span></a>
                <?php else: ?>
                    <h3>No announcements yet</h3>
                    <p>Official OSAS updates will appear here once published.</p>
                <?php endif; ?>
            </section>

            <?php if ($infographics !== []): ?>
                <section class="sd-poster sd-carousel" data-ingo-carousel>
                    <div class="sd-carousel__track" data-ingo-track>
                        <?php foreach ($infographics as $i => $ingo): ?><div class="sd-carousel__slide"><img src="{!! url('/student/infographics/' . (int) $ingo['id'] . '/image') !!}" alt="OSAS infographic {{ $i + 1 }}"{!! $i === 0 ? '' : ' loading="lazy"' !!}></div><?php endforeach; ?>
                    </div>
                    <?php if (count($infographics) > 1): ?>
                        <button type="button" class="sd-carousel__pause" data-ingo-pause aria-label="Pause slideshow">
                            <span class="sd-carousel__pause-icon" aria-hidden="true">{!! App\Support\Icons::render('pause', 16) !!}</span>
                            <span class="sd-carousel__play-icon u-hide" aria-hidden="true">{!! App\Support\Icons::render('play', 16) !!}</span>
                        </button>
                        <div class="sd-carousel__dots" role="tablist" aria-label="Infographic pictures">
                            <?php foreach ($infographics as $i => $ingo): ?><button type="button" class="pdot{!! $i === 0 ? ' pdot--on' : '' !!}" data-ingo-dot aria-label="Picture {{ $i + 1 }}"></button><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <section class="sd-poster">
                    <img src="{!! asset('img/choose-health-poster.svg') !!}" alt="Choose health, not smoke" loading="lazy">
                </section>
            <?php endif; ?>
        </div>

        <aside class="sd-side">
            <section class="sd-card sd-side-card">
                <?php
                $orCompleted = (int) ($orientation['completed'] ?? 0);
                $orTotal = (int) ($orientation['total'] ?? 0);
                $orPercent = (float) ($orientation['percent'] ?? 0);
                $isFullyCompliant = $orTotal > 0 && $orCompleted >= $orTotal;
                ?>
                <div style="display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 4px;">
                    <strong style="font-size: 14px; color: var(--ink);">{!! $orCompleted !!} of {!! $orTotal !!} Completed</strong>
                    <span style="font-size: 12px; font-weight: 700; color: {!! $isFullyCompliant ? 'var(--green)' : 'var(--brand)' !!};">{!! $orPercent !!}%</span>
                </div>
                <div class="or-compliance-card__bar-wrap">
                    <div class="or-compliance-card__bar{!! $isFullyCompliant ? ' or-compliance-card__bar--complete' : '' !!}" style="width: {!! $orPercent !!}%;"></div>
                </div>
                <div class="or-compliance-card__meta">
                    <?php if ($isFullyCompliant): ?>
                        <span class="or-compliance-card__badge or-compliance-card__badge--complete">{!! App\Support\Icons::render('checkCircle', 13) !!} Compliant</span>
                    <?php else: ?>
                        <span class="or-compliance-card__badge or-compliance-card__badge--pending">{!! App\Support\Icons::render('clock', 13) !!} {!! max(0, $orTotal - $orCompleted) !!} {!! (max(0, $orTotal - $orCompleted) === 1) ? 'module' : 'modules' !!} remaining</span>
                    <?php endif; ?>
                    <span>Required for clearance</span>
                </div>
                <?php if ($featuredModule !== null && !$isFullyCompliant): ?>
                    <p style="margin-top: 10px;">Next module: <strong>{{ $featuredModule['title'] }}</strong></p>
                    <a class="sd-link" href="{!! url('/student/orientation/' . $featuredModule['public_id']) !!}">Continue Orientation <span>&rarr;</span></a>
                <?php else: ?>
                    <a class="sd-link" href="{!! url('/student/orientation') !!}">View Orientation Modules <span>&rarr;</span></a>
                <?php endif; ?>
            </section>

            <section class="sd-card sd-side-card">
                <?php if (($activeTickets ?? 0) > 0): ?>
                    <p>You have <strong>{!! (int) $activeTickets !!}</strong> active {!! $activeTickets === 1 ? 'ticket' : 'tickets' !!} currently with OSAS.</p>
                <?php else: ?>
                    <p>Have an inquiry or student concern? Submit a support ticket to OSAS.</p>
                <?php endif; ?>
                <a class="sd-link" href="{!! url('/student/support') !!}">Open Support Desk <span>&rarr;</span></a>
            </section>

            <?php if (($activeRequests ?? 0) > 0): ?>
                <section class="sd-card sd-side-card">
                    <p>You have <strong>{!! (int) $activeRequests !!}</strong> active {!! $activeRequests === 1 ? 'request' : 'requests' !!} in process.</p>
                    <a class="sd-link" href="{!! url('/student/document-requests') !!}">Track Requests <span>&rarr;</span></a>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</div>

@endsection
