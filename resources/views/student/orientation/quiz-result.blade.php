@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $module */
/** @var array<string, mixed> $quiz */
/** @var array<string, mixed> $attempt */
/** @var list<array<string, mixed>> $questions */
/** @var array<int, array<string, mixed>> $answers */
$passed = (int) $attempt['passed'] === 1;
$percent = (float) $attempt['percent'];
$seconds = max(0, strtotime((string) ($attempt['submitted_at'] ?? $attempt['started_at'])) - strtotime((string) $attempt['started_at']));
$timeUsed = sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
$correctCount = 0;
$totalQuestions = count($questions);

foreach ($questions as $question) {
    $answer = $answers[(int) $question['id']] ?? null;

    if ($answer !== null && (int) $answer['is_correct'] === 1) {
        $correctCount++;
    }
}
$missedCount = max(0, $totalQuestions - $correctCount);
$objectives = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($module['objectives'] ?? '')) ?: []));
?>
<div class="or-result">
    <div class="or-result__head">
        <div class="or-result__badges">
            <span class="or-code">{{ $module['public_id'] }}</span>
            <span class="or-quiz__tag">ASSESSMENT RESULT</span>
        </div>
        <span class="or-badge {!! $passed ? 'or-badge--mandatory' : 'or-badge--failed' !!}">{!! $passed ? 'Passed' : 'Not passed' !!}</span>
    </div>

    <h1 class="or-result__title">{{ $module['title'] }}</h1>

    <?php if ($passed): ?>
        <div class="dr-stepper-alert" style="background: var(--green-bg); border: 1px solid var(--green-line); color: var(--green); margin-bottom: 16px;">
            {!! App\Support\Icons::render('checkCircle', 22) !!}
            <div>
                <strong>Compliance Requirement Satisfied</strong>
                <div>You have met the required passing score for this orientation module. Your academic clearance progress has been updated.</div>
            </div>
        </div>
    <?php else: ?>
        <div class="dr-stepper-alert dr-stepper-alert--danger" style="margin-bottom: 16px;">
            {!! App\Support\Icons::render('alert', 22) !!}
            <div>
                <strong>Passing Score Not Reached ({!! number_format($percent, 1) !!}%)</strong>
                <div>You needed <strong>{!! number_format((float) $module['passing_rate'], 0) !!}%</strong> to pass. You answered {!! $correctCount !!} correctly and missed {!! $missedCount !!} {!! $missedCount === 1 ? 'question' : 'questions' !!}. You can review the material and retake the assessment.</div>
            </div>
        </div>
    <?php endif; ?>

    <section class="card panel-card or-result__summary">
        <div class="or-result__ring" style="background: conic-gradient({!! $passed ? '#1f9d55' : '#c62828' !!} {!! min(100, $percent) !!}%, #eee 0);">
            <span>{!! number_format($percent, 1) !!}%</span>
            <small>{!! number_format($correctCount) !!} of {!! number_format($totalQuestions) !!} correct</small>
        </div>
        <div class="or-result__facts">
            <div><span>Required passing rate</span><strong>{!! number_format((float) $module['passing_rate'], 0) !!}%</strong></div>
            <div><span>Completed on</span><strong>{{ fmt_datetime($attempt['submitted_at'] ?? $attempt['started_at']) }}</strong></div>
            <div><span>Time used</span><strong>{{ $timeUsed }}</strong></div>
        </div>
    </section>

    <?php if (!$passed && $objectives !== []): ?>
        <section class="card panel-card" style="margin-bottom: 18px; border-left: 4px solid var(--amber);">
            <div class="panel-card__head">
                <div>
                    <div class="panel-title" style="color: var(--amber);">Remediation Recommendations</div>
                    <p class="panel-card__sub">Focus on these core module objectives before your next attempt:</p>
                </div>
            </div>
            <ul style="display: flex; flex-direction: column; gap: 8px; padding: 4px 0;">
                <?php foreach ($objectives as $obj): ?>
                    <li style="display: flex; align-items: baseline; gap: 8px; font-size: 13px; color: var(--ink);">
                        <span style="color: var(--amber); font-weight: 700;">&bull;</span>
                        <span>{{ $obj }}</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <div class="or-section-label">Answer Review &amp; Analysis</div>
    <div class="or-result__review">
        <?php foreach ($questions as $question): ?>
            <?php
            $answer = $answers[(int) $question['id']] ?? null;
            $isCorrect = $answer !== null && (int) $answer['is_correct'] === 1;
            $correctOption = null;

            foreach ($question['options'] as $option) {
                if ((int) $option['is_correct'] === 1) {
                    $correctOption = $option;
                    break;
                }
            }
            ?>
            <div class="or-result__item {!! $isCorrect ? 'or-result__item--correct' : 'or-result__item--wrong' !!}">
                <span class="or-result__mark">{!! $isCorrect ? '✓' : '✕' !!}</span>
                <div>
                    <div class="or-result__question">{{ $question['prompt'] }}</div>
                    <div class="or-result__answer">Correct answer: <strong>{{ $correctOption['label'] ?? 'Not set' }}</strong></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="or-result__actions">
        <a class="btn btn--ghost" href="{!! url('/student/orientation/' . $module['public_id']) !!}">&larr; Back to module</a>
        <?php if (!$passed): ?>
            <form method="POST" action="{!! url('/student/orientation/' . $module['public_id'] . '/quiz/start') !!}">{!! csrf_field() !!}<button class="btn or-btn-next" type="submit">RETAKE ASSESSMENT</button></form>
        <?php endif; ?>
    </div>
</div>

@endsection
