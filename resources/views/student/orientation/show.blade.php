@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $module */
/** @var list<string> $objectives */
/** @var array<string, mixed>|null $progress */
/** @var array<string, mixed>|null $mediaFile */
/** @var array<string, mixed>|null $quiz */
/** @var list<array<string, mixed>> $quizAttempts */
/** @var array<string, mixed>|null $latestAttempt */
$status = $progress['status'] ?? 'Not Started';
$passed = $latestAttempt !== null && (int) $latestAttempt['passed'] === 1;
$isVideo = $mediaFile !== null && str_starts_with((string) ($mediaFile['mime_type'] ?? ''), 'video/');
$embedUrl = video_embed_url((string) ($module['video_url'] ?? ''));
$contentType = (string) $module['content_type'];
$isFileContent = in_array($contentType, ['File', 'Document / PDF', 'Slide Deck'], true);
$contentLabel = match ($contentType) {
    'File', 'Document / PDF', 'Slide Deck' => 'FILE',
    'Link' => 'VIDEO',
    'Video Presentation' => 'VIDEO',
    'Web URL / Embed' => 'VIDEO',
    default => $contentType,
};
?>
<div class="or-detail">
    <div class="or-detail__head">
        <div>
            <h1 class="or-detail__title">{{ $module['title'] }}</h1>
            <div class="or-detail__meta">{{ $contentLabel }} + {!! $quiz !== null ? 'Quiz' : 'Module' !!} &middot; {!! (int) $module['duration_minutes'] !!} mins &middot; {{ $module['department'] ?: 'Office of Student Affairs and Services' }}</div>
            <?php if (!empty($module['compliance_deadline'])): ?>
                <div class="or-detail__deadline"><strong>Compliance Deadline:</strong> {{ fmt_date($module['compliance_deadline']) }}</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="or-section-label">Summary / Overview Description</div>
    <p class="or-detail__text">{{ $module['description'] }}</p>

    <?php if ($objectives !== []): ?>
        <div class="or-section-label">Learning Objectives</div>
        <ul class="or-objectives">
            <?php foreach ($objectives as $objective): ?><li>{!! App\Support\Icons::render('check', 13) !!} {{ $objective }}</li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="or-section-label">Content Type: {{ $contentLabel }}</div>
    <?php if ($isFileContent): ?>
        <?php if ($mediaFile !== null): ?>
            <div class="or-file-card">
                <span class="or-file-card__icon">{!! App\Support\Icons::render('fileText', 22) !!}</span>
                <div class="or-file-card__body">
                    <div class="or-file-card__title">{{ $mediaFile['original_name'] ?? 'Module file' }}</div>
                    <div class="or-file-card__meta">{{ format_bytes((int) ($mediaFile['size_bytes'] ?? 0)) }} &middot; {{ $mediaFile['mime_type'] ?? 'File' }}</div>
                </div>
                <a class="btn btn--primary" href="{!! url('/student/orientation/' . $module['public_id'] . '/media') !!}" download>{!! App\Support\Icons::render('download', 14) !!} Download File</a>
            </div>
        <?php else: ?>
            <div class="tracking-empty">No file has been attached to this module yet.</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="or-player{!! $embedUrl !== null ? ' or-player--embed' : '' !!}">
            <?php if ($embedUrl !== null): ?>
                <iframe src="{{ $embedUrl }}" title="{{ $module['title'] }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            <?php elseif ($isVideo): ?>
                <video controls preload="metadata" src="{!! url('/student/orientation/' . $module['public_id'] . '/media') !!}"></video>
            <?php else: ?>
                <div class="or-player__mock">
                    <span class="or-player__play" aria-hidden="true"></span>
                    <div class="or-player__bar"><span style="width: 0%"></span></div>
                    <div class="or-player__time">0:00 / {!! (int) $module['duration_minutes'] !!}:00</div>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($embedUrl !== null || $isVideo): ?>
            <div class="or-player__note">{!! App\Support\Icons::render('info', 13) !!} Watch the full video before taking the assessment or acknowledging the module.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="or-section-label">Completion Requirement</div>
    <section class="card panel-card or-requirement">
        <?php if ($quiz !== null): ?>
            <div class="or-requirement__alert">
                <span class="or-requirement__icon">{!! App\Support\Icons::render('alert', 16) !!}</span>
                <div><strong>Quiz Assessment Attached</strong><span>Passing score is required to complete this module.</span></div>
            </div>
            <div class="or-requirement__row"><span>Required Passing Rate</span><strong>{!! number_format((float) $module['passing_rate'], 0) !!}%</strong></div>
            <div class="or-requirement__row"><span>Your Current Status</span><strong>{!! $passed ? 'Assessment Passed' : ($latestAttempt !== null ? 'Not Passed Yet' : 'Pending Quiz Attempt') !!}</strong></div>
            <?php if (!$passed): ?>
                <form method="POST" action="{!! url('/student/orientation/' . $module['public_id'] . '/quiz/start') !!}">
                    {!! csrf_field() !!}
                    <button class="btn or-take-btn" type="submit">{!! $quizAttempts === [] ? 'TAKE MODULE QUIZ ASSESSMENT' : 'RETAKE MODULE QUIZ ASSESSMENT' !!}</button>
                </form>
            <?php else: ?>
                <div class="or-requirement__done">{!! App\Support\Icons::render('checkCircle', 18) !!} Assessment passed. Module complete.</div>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($module['verification_method'] === 'Quiz Assessment'): ?>
                <div class="or-requirement__alert">
                    <span class="or-requirement__icon">{!! App\Support\Icons::render('alert', 16) !!}</span>
                    <div><strong>Assessment not yet available</strong><span>The coordinator has not published a quiz for this module yet. You can acknowledge the module to complete it.</span></div>
                </div>
            <?php endif; ?>
            <div class="or-requirement__row"><span>Completion method</span><strong>Watch the video, then acknowledge this module.</strong></div>
            <?php if ($status !== 'Completed'): ?>
                <form method="POST" action="{!! url('/student/orientation/' . $module['public_id'] . '/complete') !!}">
                    {!! csrf_field() !!}
                    <button class="btn or-take-btn" type="submit">I ACKNOWLEDGE THIS MODULE</button>
                </form>
            <?php else: ?>
                <div class="or-requirement__done">{!! App\Support\Icons::render('checkCircle', 18) !!} Module complete.</div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

@endsection
