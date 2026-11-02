<?php
/** @var array<string, mixed>|null $announcement */
/** @var array<string, mixed> $values */
/** @var list<array<string, mixed>> $attachments */
/** @var array<string, string> $errors */
$isEdit = $announcement !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/announcements/' . $announcement['public_id']) : url('/admin/announcements');
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/admin/announcements') ?>">Announcements</a> / <?= $isEdit ? 'Edit' : 'Create' ?></div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/announcements/' . $announcement['public_id']) : url('/admin/announcements') ?>">Cancel</a>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span>
        <div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data" class="card form-card">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('megaphone', 14) ?> Notice Details</div>
        <div class="form-grid">
            <div class="field">
                <label for="title">Title <span class="req">*</span></label>
                <input class="input<?= $invalid('title') ?>" id="title" name="title" value="<?= e($values['title'] ?? '') ?>" maxlength="200" required>
                <?php if ($errorFor('title')): ?><div class="form-error"><?= e($errorFor('title')) ?></div><?php endif; ?>
            </div>
            <div class="form-grid form-grid--3">
                <div class="field">
                    <label for="category">Category <span class="req">*</span></label>
                    <input class="input<?= $invalid('category') ?>" id="category" name="category" list="announcement-categories" value="<?= e($values['category'] ?? '') ?>" required>
                    <datalist id="announcement-categories">
                        <?php foreach (['Registrar & Enrollment', 'Scholarships & Financial Aid', 'Student Affairs', 'Academic Affairs', 'Health & Wellness', 'Campus Life', 'Safety & Security'] as $category): ?><option value="<?= e($category) ?>"><?php endforeach; ?>
                    </datalist>
                    <?php if ($errorFor('category')): ?><div class="form-error"><?= e($errorFor('category')) ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="audience">Audience <span class="req">*</span></label>
                    <select class="input<?= $invalid('audience') ?>" id="audience" name="audience">
                        <?php foreach (['All Student Portals', 'Incoming Freshmen', 'Student Officers', 'Faculty & Staff', 'Graduating Students'] as $audience): ?>
                            <option value="<?= e($audience) ?>"<?= ($values['audience'] ?? '') === $audience ? ' selected' : '' ?>><?= e($audience) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($errorFor('audience')): ?><div class="form-error"><?= e($errorFor('audience')) ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="priority">Priority</label>
                    <select class="input" id="priority" name="priority">
                        <?php foreach (['Standard Priority', 'Information', 'High Priority', 'Urgent'] as $priority): ?>
                            <option value="<?= e($priority) ?>"<?= ($values['priority'] ?? '') === $priority ? ' selected' : '' ?>><?= e($priority) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="summary">Short summary</label>
                <textarea class="input" id="summary" name="summary" rows="2" maxlength="1000" placeholder="Shown in the announcement list and notification preview."><?= e($values['summary'] ?? '') ?></textarea>
                <?php if ($errorFor('summary')): ?><div class="form-error"><?= e($errorFor('summary')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="body">Full announcement <span class="req">*</span></label>
                <textarea class="input input--rich" id="body" name="body" rows="14" required><?= e($values['body'] ?? '') ?></textarea>
                <div class="help-text">Basic HTML is supported: paragraphs, headings, bold, lists and links. Scripts and event attributes are removed on save.</div>
                <?php if ($errorFor('body')): ?><div class="form-error"><?= e($errorFor('body')) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('calendar', 14) ?> Publishing</div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="status">Status</label>
                <select class="input" id="status" name="status">
                    <?php foreach (['Draft', 'Scheduled', 'Published', 'Archived'] as $status): ?>
                        <option value="<?= e($status) ?>"<?= ($values['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">Publishing sends a notification to the selected student audience.</div>
            </div>
            <div class="field">
                <label for="scheduled_for">Schedule date and time</label>
                <input class="input<?= $invalid('scheduled_for') ?>" id="scheduled_for" name="scheduled_for" type="datetime-local" value="<?= e(datetime_input($values['scheduled_for'] ?? '')) ?>">
                <?php if ($errorFor('scheduled_for')): ?><div class="form-error"><?= e($errorFor('scheduled_for')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="author">Displayed author</label>
                <input class="input<?= $invalid('author') ?>" id="author" name="author" value="<?= e($values['author'] ?? 'OSAS Administration') ?>" maxlength="150">
                <?php if ($errorFor('author')): ?><div class="form-error"><?= e($errorFor('author')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="attachment">Attachment</label>
                <input class="input<?= $invalid('attachment') ?>" id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                <div class="help-text">Optional PDF, JPG or PNG up to 5 MB. Files are stored outside the public web root.</div>
                <?php if ($errorFor('attachment')): ?><div class="form-error"><?= e($errorFor('attachment')) ?></div><?php endif; ?>
            </div>
        </div>

        <?php if ($attachments !== []): ?>
            <div class="attachment-list">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="attachment-chip">
                        <?= App\Support\Icons::render('fileText', 14) ?>
                        <span><?= e($attachment['name'] ?? 'Attachment') ?></span>
                        <em><?= e($attachment['size'] ?? '') ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="form-actions">
        <a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/announcements/' . $announcement['public_id']) : url('/admin/announcements') ?>">Cancel</a>
        <button class="btn btn--primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Announcement' ?></button>
    </div>
</form>
