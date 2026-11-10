@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $overview */
$maxAnnouncementViews = max(1, max(array_column($overview['top_announcements'], 'views') ?: [0]));
$maxOrientationOpens = max(1, max(array_column($overview['top_orientations'], 'learners') ?: [0]));
$accountCounts = [];
foreach ($overview['account_statuses'] as $status) {
    $accountCounts[(string) $status['status']] = (int) $status['count'];
}
$accountTotal = array_sum($accountCounts);
$studentProgramTotal = array_sum(array_column($overview['student_programs'], 'count'));
$programColors = ['#8d1a28', '#0f8a4d', '#a9660b', '#3569a8', '#7356a5', '#16838a', '#c14f75', '#687382'];
$queueCharts = [
    [
        'title' => 'Support & Concerns',
        'labels' => ['Open', 'In progress', 'Resolved', 'Closed'],
        'values' => [
            $overview['operational_queue']['support_open'],
            $overview['operational_queue']['support_in_progress'],
            $overview['operational_queue']['support_resolved'],
            $overview['operational_queue']['support_closed'],
        ],
    ],
    [
        'title' => 'Document Requests',
        'labels' => array_keys($overview['operational_queue']['documents']),
        'values' => array_values($overview['operational_queue']['documents']),
    ],
    [
        'title' => 'Lost & Found',
        'labels' => array_keys($overview['operational_queue']['lost_found']),
        'values' => array_values($overview['operational_queue']['lost_found']),
    ],
    [
        'title' => 'Events & Activities',
        'labels' => array_keys($overview['operational_queue']['events']),
        'values' => array_values($overview['operational_queue']['events']),
    ],
    [
        'title' => 'Announcements',
        'labels' => array_keys($overview['operational_queue']['announcements']),
        'values' => array_values($overview['operational_queue']['announcements']),
    ],
];
$healthIssueCount = count(array_filter(
    $overview['health_checks'],
    static fn (array $check): bool => !$check['ok'],
));
?>
<div class="technical-dashboard">
    <header class="technical-dashboard__head">
        <div>
            <p class="technical-dashboard__eyebrow">TECHNICAL ADMIN · SYSTEM OVERVIEW</p>
            <h2>Welcome back, {{ $user['first_name'] ?? 'Administrator' }}.</h2>
            <p>Monitor account activity, system health, and engagement across the portal.</p>
        </div>
        <div class="technical-dashboard__tools">
            <span class="technical-range"><span></span> Last 7 days</span>
            <a href="{!! url('/admin/activity') !!}" class="btn btn--outline">Audit Log</a>
            <a href="{!! url('/admin/system-overview') !!}" class="btn btn--primary">{!! App\Support\Icons::render('refresh', 14) !!} Refresh</a>
        </div>
    </header>

    <div class="stats technical-dashboard__metrics tw-mb-5 tw-gap-4">
        <div class="stat tw-rounded-xl tw-bg-white tw-p-5 tw-shadow-sm">
            <div class="stat__label">Active Accounts</div>
            <div class="stat__value">{!! number_format($accountCounts['Active'] ?? 0) !!}</div>
            <div class="stat__sub">of {!! number_format($accountTotal) !!} total accounts</div>
        </div>
        <div class="stat tw-rounded-xl tw-bg-white tw-p-5 tw-shadow-sm">
            <div class="stat__label">Pending Accounts</div>
            <div class="stat__value">{!! number_format($accountCounts['Pending'] ?? 0) !!}</div>
            <div class="stat__sub">Awaiting verification</div>
        </div>
        <div class="stat tw-rounded-xl tw-bg-white tw-p-5 tw-shadow-sm">
            <div class="stat__label">Signed In Today</div>
            <div class="stat__value">{!! number_format($overview['active_today']) !!}</div>
            <div class="stat__sub">Distinct accounts today</div>
        </div>
        <div class="stat tw-rounded-xl tw-bg-white tw-p-5 tw-shadow-sm">
            <div class="stat__label">Failed Sign-Ins (24h)</div>
            <div class="stat__value">{!! number_format($overview['failed_logins_24h']) !!}</div>
            <div class="stat__sub">Recorded failed attempts</div>
        </div>
    </div>

    <div class="dash-grid technical-overview tw-gap-5">
    <section class="card panel-card technical-activity-card tw-rounded-2xl tw-bg-white tw-p-5 tw-shadow-sm">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">Sign-In Activity — Last 7 Days</div>
                <p class="panel-card__sub">Successful account sign-ins and failed attempts, day by day.</p>
            </div>
        </div>
        <div class="technical-chart-legend">
            <span><i class="technical-chart-key technical-chart-key--login"></i>Accounts signed in</span>
            <span><i class="technical-chart-key technical-chart-key--failed"></i>Failed attempts</span>
        </div>
        <?php if ($overview['activity_trend'] !== []): ?>
            <div class="technical-chart technical-chart--activity tw-rounded-xl tw-bg-slate-50 tw-p-3">
                <canvas
                    data-technical-chart="activity"
                    data-chart-labels="{{ json_encode(array_map(
                        static fn (array $day): array => [$day['label'], date('M j', strtotime($day['date']))],
                        $overview['activity_trend'],
                    )) }}"
                    data-chart-logins="{{ json_encode(array_map('intval', array_column($overview['activity_trend'], 'logins'))) }}"
                    data-chart-failed="{{ json_encode(array_map('intval', array_column($overview['activity_trend'], 'failed'))) }}"
                    role="img"
                    aria-label="Daily accounts signed in and failed sign-in attempts over the last seven days"
                >Daily sign-ins and failed attempts for the last seven days.</canvas>
            </div>
            <p class="technical-chart-note">Hover or tap a day to compare sign-ins and failed attempts.</p>
        <?php else: ?>
            <p class="technical-empty">No sign-in activity recorded in this period.</p>
        <?php endif; ?>
    </section>

    <section class="card panel-card technical-status-card tw-rounded-2xl tw-bg-white tw-p-5 tw-shadow-sm">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">Account Status Distribution</div>
                <p class="panel-card__sub">{!! number_format($accountTotal) !!} total accounts</p>
            </div>
        </div>
        <?php if ($accountTotal > 0): ?>
            <div class="technical-status-chart">
                <div class="technical-donut-chart">
                    <canvas
                        data-technical-chart="status"
                        data-chart-labels="{{ json_encode(['Active', 'Pending', 'Inactive']) }}"
                        data-chart-values="{{ json_encode([
                            $accountCounts['Active'] ?? 0,
                            $accountCounts['Pending'] ?? 0,
                            $accountCounts['Inactive'] ?? 0,
                        ]) }}"
                        data-chart-total="{!! $accountTotal !!}"
                        role="img"
                        aria-label="Account status distribution: {!! number_format($accountCounts['Active'] ?? 0) !!} active, {!! number_format($accountCounts['Pending'] ?? 0) !!} pending, {!! number_format($accountCounts['Inactive'] ?? 0) !!} inactive"
                    >Account status distribution.</canvas>
                </div>
            </div>
            <div class="technical-status-legend">
                <?php foreach (['Active' => 'green', 'Pending' => 'amber', 'Inactive' => 'muted'] as $statusName => $tone): ?>
                    <?php $count = $accountCounts[$statusName] ?? 0; ?>
                    <div class="technical-status-legend__item">
                        <span class="technical-status-legend__swatch technical-status-legend__swatch--{!! $tone !!}"></span>
                        <span>{{ $statusName }}</span>
                        <strong>{!! number_format($count) !!}</strong>
                        <small>{!! number_format(($count / $accountTotal) * 100, 1) !!}%</small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="technical-empty">No accounts are recorded yet.</p>
        <?php endif; ?>
    </section>
</div>

    <div class="dash-grid technical-overview tw-gap-5">
    <?php foreach ($queueCharts as $chart): ?>
        <section class="card panel-card technical-queue-card tw-rounded-2xl tw-bg-white tw-p-5 tw-shadow-sm">
            <div class="panel-card__head">
                <div>
                    <div class="panel-title">{{ $chart['title'] }}</div>
                    <p class="panel-card__sub">Current operational count</p>
                </div>
            </div>
            <div class="technical-chart technical-chart--queue tw-rounded-xl tw-bg-slate-50 tw-p-3">
                <canvas
                    data-technical-chart="queue"
                    data-chart-labels="{{ json_encode($chart['labels']) }}"
                    data-chart-values="{{ json_encode(array_map('intval', $chart['values'])) }}"
                    role="img"
                    aria-label="{{ $chart['title'] . ' counts: ' . implode('; ', array_map(
                        static fn (string $label, int $count): string => $label . ': ' . number_format($count),
                        $chart['labels'],
                        $chart['values'],
                    )) }}"
                >{{ $chart['title'] }} counts.</canvas>
            </div>
        </section>
    <?php endforeach; ?>
    </div>

    <div class="dash-grid technical-overview tw-gap-5">
    <section class="card panel-card technical-program-card tw-rounded-2xl tw-bg-white tw-p-5 tw-shadow-sm">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">Students by Program</div>
                <p class="panel-card__sub">Percentage of {!! number_format($studentProgramTotal) !!} student accounts in each program.</p>
            </div>
        </div>
        <?php if ($studentProgramTotal > 0): ?>
            <div class="technical-program-content">
                <div class="technical-donut-chart technical-donut-chart--program">
                    <canvas
                        data-technical-chart="program"
                        data-chart-labels="{{ json_encode(array_column($overview['student_programs'], 'program')) }}"
                        data-chart-values="{{ json_encode(array_map('intval', array_column($overview['student_programs'], 'count'))) }}"
                        data-chart-total="{!! $studentProgramTotal !!}"
                        data-chart-colors="{{ json_encode(array_map(
                            static fn (int $index): string => $programColors[$index % count($programColors)],
                            array_keys($overview['student_programs']),
                        )) }}"
                        role="img"
                        aria-label="Student account distribution by program: {{ implode(', ', array_map(
                            static fn (array $program): string => $program['program'] . ', ' . number_format(((int) $program['count'] / $studentProgramTotal) * 100, 1) . '%',
                            $overview['student_programs'],
                        )) }}"
                    >Student account percentages by program.</canvas>
                </div>
                <div class="technical-program-legend">
                    <?php foreach ($overview['student_programs'] as $index => $program): ?>
                        <?php $percentage = ((int) $program['count'] / $studentProgramTotal) * 100; ?>
                        <div class="technical-program-legend__item">
                            <span class="technical-program-legend__swatch" style="background: {{ $programColors[$index % count($programColors)] }}"></span>
                            <span class="technical-program-legend__name">{{ $program['program'] }}</span>
                            <strong>{!! number_format((int) $program['count']) !!}</strong>
                            <small>{!! number_format($percentage, 1) !!}%</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <p class="technical-empty">No student accounts are recorded yet.</p>
        <?php endif; ?>
    </section>
</div>

    <div class="dash-grid technical-overview tw-gap-5">
    <section class="card panel-card technical-health-card tw-rounded-2xl tw-bg-white tw-p-5 tw-shadow-sm">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">System Health Checks</div>
                <p class="panel-card__sub">{!! $healthIssueCount === 0 ? 'All checks passed for this request.' : number_format($healthIssueCount) . ' check(s) need attention.' !!}</p>
            </div>
        </div>
        <div class="technical-checks">
            <?php foreach ($overview['health_checks'] as $check): ?>
                <div class="technical-check">
                    <div>
                        <strong>{{ $check['label'] }}</strong>
                        <span>{{ $check['detail'] }}</span>
                    </div>
                    <span class="badge {!! $check['ok'] ? 'badge--verified' : 'badge--high' !!}">{!! $check['ok'] ? 'OK' : 'Needs attention' !!}</span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="technical-backup-note">
            <strong>Backups are not monitored by the portal.</strong>
            <span>Confirm database and uploaded-file backups with your hosting provider; keep copies outside the public web directory.</span>
        </div>
    </section>

    <section class="card panel-card technical-content-card tw-rounded-2xl tw-bg-white tw-p-5 tw-shadow-sm">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">Most Viewed Published Content</div>
                <p class="panel-card__sub">All-time views recorded by the portal, not unique viewers.</p>
            </div>
        </div>
        <div class="technical-content-group">
            <h3>Announcements</h3>
            <?php if ($overview['top_announcements'] === []): ?>
                <p class="technical-empty">No published announcements.</p>
            <?php else: ?>
                <?php foreach ($overview['top_announcements'] as $item): ?>
                    <?php $width = (int) round(((int) $item['views'] / $maxAnnouncementViews) * 100); ?>
                    <div class="technical-ranking-item" title="{!! number_format((int) $item['views']) !!} views">
                        <div class="technical-ranking-item__head">
                            <span class="technical-content-title">{{ $item['title'] }}</span>
                            <strong>{!! number_format((int) $item['views']) !!}</strong>
                        </div>
                        <div class="technical-ranking-track"><span style="width: {!! $width !!}%"></span></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="technical-content-group">
            <h3>Orientation Modules</h3>
            <?php if ($overview['top_orientations'] === []): ?>
                <p class="technical-empty">No published orientation modules.</p>
            <?php else: ?>
                <?php foreach ($overview['top_orientations'] as $item): ?>
                    <?php $width = (int) round(((int) $item['learners'] / $maxOrientationOpens) * 100); ?>
                    <div class="technical-ranking-item" title="{!! number_format((int) $item['learners']) !!} opens, {!! number_format((int) $item['completions']) !!} completions">
                        <div class="technical-ranking-item__head">
                            <span class="technical-content-title">{{ $item['title'] }}</span>
                            <strong>{!! number_format((int) $item['learners']) !!}</strong>
                        </div>
                        <div class="technical-ranking-track"><span style="width: {!! $width !!}%"></span></div>
                        <span class="technical-ranking-caption">{!! number_format((int) $item['completions']) !!} completions</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

@endsection
