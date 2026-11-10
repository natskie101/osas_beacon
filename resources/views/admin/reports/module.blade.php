@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $module */
/** @var list<array<string, mixed>> $completions */
?>
<div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="{!! url('/admin/reports') !!}">Reports</a> / {{ $module['public_id'] }}</div>
        <h2 class="page-head__title">{{ $module['title'] }}</h2>
        <p class="page-head__sub">{{ $module['public_id'] }} &middot; {{ $module['version'] }} &middot; {{ $module['category'] }}</p>
    </div>
    <div class="page-head__actions">
        <a class="editor-close" href="{!! url('/admin/reports') !!}" aria-label="Close">&times;</a>
    </div>
</div>

<section class="card panel-card report-facts">
    <div><span>Completed</span><strong>{!! number_format((int) $module['completed']) !!} / {!! number_format((int) $module['total_students']) !!}</strong></div>
    <div><span>Completion</span><strong>{!! number_format((float) $module['percent'], 0) !!}%</strong></div>
    <div><span>Est. Duration</span><strong>{!! number_format((int) $module['duration_minutes']) !!} mins</strong></div>
    <div><span>Last Updated</span><strong>{{ fmt_date($module['updated_at']) }}</strong></div>
</section>

<section class="card panel-card report-facts report-facts--quiz">
    <div><span>Quiz Status</span><strong>{{ $module['quiz_status'] ?? 'No assessment' }}</strong></div>
    <div><span>Quiz Attempts</span><strong>{!! number_format((int) $module['quiz_attempts']) !!}</strong></div>
    <div><span>Quiz Pass Rate</span><strong>{!! number_format((float) $module['quiz_pass_rate'], 0) !!}%</strong></div>
    <div><span>Average Score</span><strong>{!! number_format((float) $module['quiz_average'], 0) !!}%</strong></div>
</section>

<div class="panel-title report-section-title">1. Students Who Completed This Module</div>

<div class="table-wrap">
    <?php if ($completions === []): ?>
        <div class="empty-state"><strong>No students have completed this module yet.</strong></div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data">
                <thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Year Level</th><th>Date Completed</th></tr></thead>
                <tbody>
                    <?php foreach ($completions as $row): ?>
                        <tr>
                            <td>{{ $row['student_id'] ?: $module['public_id'] }}</td>
                            <td>{{ trim($row['first_name'] . ' ' . $row['last_name']) }}</td>
                            <td>{{ $row['program'] ?: 'Not assigned' }}</td>
                            <td>{{ $row['year_level'] ?: 'Not set' }}</td>
                            <td>{{ fmt_date($row['completed_at']) }}</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="form-actions">
    <a class="btn btn--ghost" href="{!! url('/admin/reports') !!}">Close</a>
    <a class="btn btn--outline" href="{!! url('/admin/reports/orientation/' . $module['public_id'] . '/csv') !!}">{!! App\Support\Icons::render('download', 13) !!} CSV</a>
    <a class="btn btn--primary" href="{!! url('/admin/reports/orientation/' . $module['public_id'] . '/excel') !!}">{!! App\Support\Icons::render('download', 13) !!} Excel</a>
</div>

@endsection
