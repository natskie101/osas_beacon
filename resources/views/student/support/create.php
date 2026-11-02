<?php
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$categories = ['Academic Concern', 'Enrollment Issue', 'Scholarship Concern', 'Student Conduct', 'Campus Facilities', 'Health and Wellness', 'Lost and Found', 'Document Request Follow-up', 'Other'];
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/student/support') ?>">Feedback and Report</a> / New Concern</div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
    <a class="btn btn--ghost" href="<?= url('/student/support') ?>">Cancel</a>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger">
        <span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span>
        <div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div>
    </div>
<?php endif; ?>

<form class="card form-card" method="POST" action="<?= url('/student/support') ?>">
    <?= csrf_field() ?>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('headset', 14) ?> Concern Details</div>
        <div class="form-grid">
            <div class="form-grid form-grid--2">
                <div class="field">
                    <label for="category">Category <span class="req">*</span></label>
                    <select class="input<?= $invalid('category') ?>" id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($values['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?>
                    </select>
                    <?php if ($errorFor('category')): ?><div class="form-error"><?= e($errorFor('category')) ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="priority">Priority</label>
                    <select class="input" id="priority" name="priority">
                        <?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $priority): ?><option value="<?= e($priority) ?>"<?= ($values['priority'] ?? '') === $priority ? ' selected' : '' ?>><?= e($priority) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="subject">Subject <span class="req">*</span></label>
                <input class="input<?= $invalid('subject') ?>" id="subject" name="subject" value="<?= e($values['subject'] ?? '') ?>" maxlength="200" required>
                <?php if ($errorFor('subject')): ?><div class="form-error"><?= e($errorFor('subject')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="description">What happened? <span class="req">*</span></label>
                <textarea class="input<?= $invalid('description') ?>" id="description" name="description" rows="7" maxlength="4000" required placeholder="Include dates, offices involved and what outcome you need."><?= e($values['description'] ?? '') ?></textarea>
                <?php if ($errorFor('description')): ?><div class="form-error"><?= e($errorFor('description')) ?></div><?php endif; ?>
            </div>
        </div>
    </section>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('info', 14) ?> What Happens Next</div>
        <p class="panel-card__text">OSAS will acknowledge your concern and reply through the portal. You will receive a notification whenever the ticket status changes or a staff member responds.</p>
    </section>
    <div class="form-actions">
        <a class="btn btn--ghost" href="<?= url('/student/support') ?>">Cancel</a>
        <button class="btn btn--primary" type="submit">Submit Concern</button>
    </div>
</form>
