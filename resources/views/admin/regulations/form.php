<?php
/** @var array<string, mixed>|null $regulation */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$isEdit = $regulation !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/regulations/' . $regulation['public_id']) : url('/admin/regulations');
?><div class="page-head">
    <div><div class="page-head__eyebrow"><a href="<?= url('/admin/regulations') ?>">Rules & Regulations</a> / <?= $isEdit ? 'Edit' : 'Add' ?></div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div>
    <div class="page-head__actions"><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/regulations/' . $regulation['public_id']) : url('/admin/regulations') ?>">Cancel</a></div>
</div>
<?php if ($errors !== []): ?><div class="alert alert--danger"><span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div></div><?php endif; ?>
<form class="card form-card" method="POST" action="<?= e($action) ?>">
    <?= csrf_field() ?><?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('scale', 14) ?> Policy Details</div>
        <div class="form-grid">
            <div class="field"><label for="title">Title <span class="req">*</span></label><input class="input<?= $invalid('title') ?>" id="title" name="title" value="<?= e($values['title'] ?? '') ?>" required><?php if ($errorFor('title')): ?><div class="form-error"><?= e($errorFor('title')) ?></div><?php endif; ?></div>
            <div class="form-grid form-grid--3">
                <div class="field"><label for="reference_code">Reference code <span class="req">*</span></label><input class="input<?= $invalid('reference_code') ?>" id="reference_code" name="reference_code" value="<?= e($values['reference_code'] ?? '') ?>" required></div>
                <div class="field"><label for="category">Category</label><input class="input" id="category" name="category" list="regulation-categories" value="<?= e($values['category'] ?? '') ?>" required><datalist id="regulation-categories"><?php foreach (['Student Code of Conduct', 'Academic Policies', 'Campus Safety', 'Student Rights', 'Discipline & Sanctions', 'Attendance'] as $category): ?><option value="<?= e($category) ?>"><?php endforeach; ?></datalist></div>
                <div class="field"><label for="audience">Audience</label><input class="input" id="audience" name="audience" value="<?= e($values['audience'] ?? 'All Students') ?>"></div>
            </div>
            <div class="field"><label for="summary">Summary</label><textarea class="input" id="summary" name="summary" rows="2"><?= e($values['summary'] ?? '') ?></textarea></div>
            <div class="field"><label for="body">Policy text <span class="req">*</span></label><textarea class="input input--rich" id="body" name="body" rows="14" required><?= e($values['body'] ?? '') ?></textarea><div class="help-text">Basic HTML is supported. Scripts and event attributes are removed on save.</div></div>
            <div class="field"><label for="sanctions">Sanctions and penalties</label><textarea class="input" id="sanctions" name="sanctions" rows="5" placeholder="One sanction per line"><?= e($values['sanctions'] ?? '') ?></textarea><div class="help-text">Each line appears as a separate item on the student-facing policy page.</div></div>
        </div>
    </section>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('calendar', 14) ?> Governance</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><?php foreach (['Draft', 'Pending Review', 'Published', 'Archived'] as $status): ?><option value="<?= e($status) ?>"<?= ($values['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="effective_date">Effective date</label><input class="input" id="effective_date" name="effective_date" value="<?= e($values['effective_date'] ?? '') ?>" placeholder="e.g. September 15, 2025"></div>
            <div class="field"><label for="review_schedule">Review schedule</label><input class="input" id="review_schedule" name="review_schedule" value="<?= e($values['review_schedule'] ?? '') ?>" placeholder="Annual review every June"></div>
            <div class="field"><label for="approval_body">Approving body</label><input class="input" id="approval_body" name="approval_body" value="<?= e($values['approval_body'] ?? '') ?>"></div>
        </div>
    </section>
    <div class="form-actions"><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/regulations/' . $regulation['public_id']) : url('/admin/regulations') ?>">Cancel</a><button class="btn btn--primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Regulation' ?></button></div>
</form>
