<?php
/** @var array<string, mixed> $module */
/** @var array<string, mixed>|null $quiz */
/** @var list<array<string, mixed>> $questions */
/** @var array<string, string> $errors */
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
?>
<div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/admin/orientation') ?>">Orientation Content</a> / <a href="<?= url('/admin/orientation/' . $module['public_id']) ?>"><?= e($module['public_id']) ?></a> / Quiz</div>
        <h2 class="page-head__title">Quiz Assessment</h2>
        <p class="page-head__sub"><?= e($module['title']) ?> &middot; <?= number_format(count($questions)) ?> questions &middot; Passing rate <?= number_format((float) $module['passing_rate'], 0) ?>%</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--outline" href="<?= url('/admin/orientation/' . $module['public_id']) ?>">Back to Module</a>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert"><span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div></div>
<?php endif; ?>

<section class="card form-card">
    <div class="section-title">Quiz Settings</div>
    <form method="POST" action="<?= url('/admin/orientation/' . $module['public_id'] . '/quiz') ?>" class="form-grid form-grid--2">
        <?= csrf_field() ?>
        <div class="field">
            <label for="title">Quiz title <span class="req">*</span></label>
            <input class="input" id="title" name="title" value="<?= e($quiz['title'] ?? $module['title'] . ' Assessment') ?>" required>
        </div>
        <div class="field">
            <label for="time_limit_minutes">Time limit (minutes) <span class="req">*</span></label>
            <input class="input" id="time_limit_minutes" name="time_limit_minutes" type="number" min="1" max="240" value="<?= e($quiz['time_limit_minutes'] ?? 20) ?>" required>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select class="input" id="status" name="status">
                <?php foreach (['Draft', 'Published'] as $status): ?><option value="<?= e($status) ?>"<?= ($quiz['status'] ?? 'Draft') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Passing score</label>
            <input class="input" value="<?= number_format((float) $module['passing_rate'], 0) ?>% (set in Add Orientation)" readonly>
        </div>
        <div class="field form-grid" style="grid-column:1 / -1">
            <label for="instructions">Instructions</label>
            <textarea class="input" id="instructions" name="instructions" rows="3"><?= e($quiz['instructions'] ?? '') ?></textarea>
        </div>
        <div class="form-actions" style="grid-column:1 / -1"><button class="btn btn--primary" type="submit">Save Quiz Settings</button></div>
    </form>
</section>

<section class="card form-card">
    <div class="section-title">Questions (<?= number_format(count($questions)) ?>)</div>
    <?php if ($questions === []): ?>
        <div class="empty-state"><strong>No questions yet.</strong><div>Add the first multiple-choice question below.</div></div>
    <?php else: ?>
        <div class="quiz-question-list">
            <?php foreach ($questions as $index => $question): ?>
                <article class="quiz-question">
                    <div class="quiz-question__head">
                        <div><strong><?= number_format($index + 1) ?>. <?= e($question['prompt']) ?></strong><span><?= number_format((int) $question['points']) ?> point<?= (int) $question['points'] === 1 ? '' : 's' ?></span></div>
                        <form method="POST" action="<?= url('/admin/orientation/' . $module['public_id'] . '/quiz/questions/' . $question['id'] . '/delete') ?>" data-confirm="Remove this question?"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Delete</button></form>
                    </div>
                    <ul class="quiz-options">
                        <?php foreach ($question['options'] as $option): ?>
                            <li class="<?= (int) $option['is_correct'] === 1 ? 'quiz-option--correct' : '' ?>">
                                <?= e($option['label']) ?>
                                <?php if ((int) $option['is_correct'] === 1): ?><span class="badge badge--verified">Correct</span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <details class="inline-details">
                        <summary class="btn btn--outline btn--sm">Edit Question</summary>
                        <form method="POST" action="<?= url('/admin/orientation/' . $module['public_id'] . '/quiz/questions/' . $question['id'] . '/update') ?>" class="inline-details__body">
                            <?= csrf_field() ?>
                            <?= App\Support\View::render('admin/orientation/partials/question-fields', ['question' => $question, 'prefix' => 'edit-' . $question['id']]) ?>
                            <button class="btn btn--primary btn--sm" type="submit">Save Question</button>
                        </form>
                    </details>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card form-card">
    <div class="section-title">Add Question</div>
    <form method="POST" action="<?= url('/admin/orientation/' . $module['public_id'] . '/quiz/questions') ?>" class="form-grid">
        <?= csrf_field() ?>
        <?= App\Support\View::render('admin/orientation/partials/question-fields', ['question' => null, 'prefix' => 'add']) ?>
        <div class="form-actions"><button class="btn btn--primary" type="submit">Add Question</button></div>
    </form>
</section>
