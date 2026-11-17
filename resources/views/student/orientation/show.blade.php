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
    'File', 'Document / PDF', 'Slide Deck' => $isVideo ? 'VIDEO' : 'FILE',
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
    <?php if ($isFileContent && !$isVideo): ?>
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
            <?php if ($embedUrl !== null && !$isFileContent): ?>
                <iframe src="{{ $embedUrl }}" title="{{ $module['title'] }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            <?php elseif ($isVideo): ?>
                <video id="moduleVideo" preload="metadata" playsinline tabindex="0" src="{!! url('/student/orientation/' . $module['public_id'] . '/media') !!}"></video>
                <div class="or-player__controls">
                    <button type="button" class="or-player__toggle" id="moduleVideoToggle" aria-label="Play video"><span class="or-player__glyph" aria-hidden="true"></span></button>
                    <div class="or-player__track" aria-hidden="true"><span id="moduleVideoFill"></span></div>
                    <span class="or-player__clock" id="moduleVideoTime">0:00 / 0:00</span>
                </div>
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
            <?php if ($passed): ?>
                <div class="or-requirement__done">{!! App\Support\Icons::render('checkCircle', 18) !!} Assessment passed. Module complete.</div>
            <?php else: ?>
                <?php if (!$videoWatched): ?>
                    <div class="or-requirement__alert" id="assessmentLock">
                        <span class="or-requirement__icon">{!! App\Support\Icons::render('lock', 16) !!}</span>
                        <div><strong>Assessment locked</strong><span>Watch the video all the way to the end to unlock the assessment.</span></div>
                    </div>
                <?php endif; ?>
                <form method="POST" action="{!! url('/student/orientation/' . $module['public_id'] . '/quiz/start') !!}">
                    {!! csrf_field() !!}
                    <button class="btn or-take-btn" type="submit" id="assessmentAction"{!! $videoWatched ? '' : ' disabled' !!}>{!! $quizAttempts === [] ? 'TAKE MODULE QUIZ ASSESSMENT' : 'RETAKE MODULE QUIZ ASSESSMENT' !!}</button>
                </form>
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
                <?php if (!$videoWatched): ?>
                    <div class="or-requirement__alert" id="assessmentLock">
                        <span class="or-requirement__icon">{!! App\Support\Icons::render('lock', 16) !!}</span>
                        <div><strong>Acknowledgement locked</strong><span>Watch the video all the way to the end to acknowledge this module.</span></div>
                    </div>
                <?php endif; ?>
                <form method="POST" action="{!! url('/student/orientation/' . $module['public_id'] . '/complete') !!}">
                    {!! csrf_field() !!}
                    <button class="btn or-take-btn" type="submit" id="assessmentAction"{!! $videoWatched ? '' : ' disabled' !!}>I ACKNOWLEDGE THIS MODULE</button>
                </form>
            <?php else: ?>
                <div class="or-requirement__done">{!! App\Support\Icons::render('checkCircle', 18) !!} Module complete.</div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<script>
(function () {
    var video = document.getElementById('moduleVideo');
    if (!video) return;

    var toggle = document.getElementById('moduleVideoToggle');
    var fill = document.getElementById('moduleVideoFill');
    var clock = document.getElementById('moduleVideoTime');
    var action = document.getElementById('assessmentAction');
    var lock = document.getElementById('assessmentLock');
    var locked = {{ $videoWatched ? 'false' : 'true' }};
    var endpoint = @json(url('/student/orientation/' . $module['public_id'] . '/video-watched'));
    var csrf = @json(csrf_token());
    var last = 0;
    var sending = false;

    function fmt(seconds) {
        if (!isFinite(seconds) || seconds < 0) seconds = 0;
        var mins = Math.floor(seconds / 60);
        var secs = Math.floor(seconds % 60);
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }

    function paint() {
        var duration = video.duration || 0;
        fill.style.width = (duration ? Math.min((video.currentTime / duration) * 100, 100) : 0) + '%';
        clock.textContent = fmt(video.currentTime) + ' / ' + fmt(duration);
    }

    function glyph() {
        var playing = !video.paused && !video.ended;
        toggle.classList.toggle('is-playing', playing);
        toggle.setAttribute('aria-label', playing ? 'Pause video' : 'Play video');
    }

    function unlock() {
        if (!locked) return;
        locked = false;
        if (lock) lock.remove();
        if (action) action.disabled = false;
    }

    function verify() {
        if (sending) return;
        sending = true;
        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            if (!response.ok) throw new Error('verification failed');
            unlock();
        }).catch(function () {
            sending = false;
            var text = lock ? lock.querySelector('strong + span') : null;
            if (text) text.textContent = 'Could not verify the video - refresh the page to try again.';
        });
    }

    toggle.addEventListener('click', function () {
        if (video.paused || video.ended) video.play();
        else video.pause();
    });

    video.addEventListener('play', glyph);
    video.addEventListener('pause', glyph);
    video.addEventListener('loadedmetadata', paint);
    video.addEventListener('timeupdate', function () {
        last = Math.max(last, video.currentTime);
        paint();
    });
    video.addEventListener('ended', function () {
        glyph();
        verify();
    });
    // No seeking forward: the control bar is gone, this catches anything else.
    video.addEventListener('seeking', function () {
        if (video.currentTime > last + 1) video.currentTime = last;
    });
    video.addEventListener('dblclick', function (event) {
        event.preventDefault();
    });
    video.addEventListener('keydown', function (event) {
        var key = event.key;
        if (key === ' ' || event.code === 'Space' || key === 'k' || key === 'K') {
            event.preventDefault();
            if (video.paused || video.ended) video.play();
            else video.pause();
        } else if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'PageUp', 'PageDown'].indexOf(key) !== -1) {
            event.preventDefault();
        }
    });

    paint();
    glyph();
})();
</script>

@endsection
