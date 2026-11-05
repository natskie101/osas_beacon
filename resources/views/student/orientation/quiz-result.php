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

foreach ($questions as $question) {
    $answer = $answers[(int) $question['id']] ?? null;

    if ($answer !== null && (int) $answer['is_correct'] === 1) {
        $correctCount++;
    }
}
?>
<div class="or-result">
    <div class="or-result__head">
        <div class="or-result__badges">
            <span class="or-code"><?= e($module['public_id']) ?></span>
            <span class="or-quiz__tag">ASSESSMENT RESULT</span>
        </div>
        <span class="or-badge <?= $passed ? 'or-badge--mandatory' : 'or-badge--failed' ?>"><?= $passed ? 'PASSED' : 'NOT PASSED' ?></span>
    </div>

    <h1 class="or-result__title"><?= e($module['title']) ?></h1>

    <section class="card panel-card or-result__summary">
        <div class="or-result__ring" style="background: conic-gradient(<?= $passed ? '#1f9d55' : '#c62828' ?> <?= min(100, $percent) ?>%, #eee 0);">
            <span><?= number_format($percent, 1) ?>%</span>
            <small><?= number_format($correctCount) ?> of <?= number_format(count($questions)) ?> correct</small>
        </div>
        <div class="or-result__facts">
            <div><span>Required passing rate</span><strong><?= number_format((float) $module['passing_rate'], 0) ?>%</strong></div>
            <div><span>Completed on</span><strong><?= e(fmt_datetime($attempt['submitted_at'] ?? $attempt['started_at'])) ?></strong></div>
            <div><span>Time used</span><strong><?= e($timeUsed) ?></strong></div>
        </div>
    </section>

    <div class="or-section-label">Answer Review</div>
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
            <div class="or-result__item <?= $isCorrect ? 'or-result__item--correct' : 'or-result__item--wrong' ?>">
                <span class="or-result__mark"><?= $isCorrect ? '✓' : '✕' ?></span>
                <div>
                    <div class="or-result__question"><?= e($question['prompt']) ?></div>
                    <div class="or-result__answer">Correct answer: <strong><?= e($correctOption['label'] ?? 'Not set') ?></strong></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="or-result__actions">
        <a class="btn btn--ghost" href="<?= url('/student/orientation/' . $module['public_id']) ?>">&larr; Back to module</a>
        <?php if (!$passed): ?>
            <form method="POST" action="<?= url('/student/orientation/' . $module['public_id'] . '/quiz/start') ?>"><?= csrf_field() ?><button class="btn or-btn-next" type="submit">RETAKE ASSESSMENT</button></form>
        <?php endif; ?>
    </div>
</div>
