<?php
/** @var array<string, mixed>|null $regulation */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$isEdit = $regulation !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/regulations/' . $regulation['public_id']) : url('/admin/regulations');
$categories = ['Student Code of Conduct', 'Academic Policies', 'Campus Safety', 'Student Rights', 'Discipline & Sanctions', 'Attendance'];
$currentCategory = (string) ($values['category'] ?? '');
if ($currentCategory !== '' && !in_array($currentCategory, $categories, true)) {
    $categories[] = $currentCategory;
}
$severities = ['Minor Offense (Level 1)', 'Major Offense (Level 2)', 'Serious Offense (Level 3)', 'Grave Offense (Level 4)'];
$currentSeverity = (string) ($values['severity'] ?? 'Minor Offense (Level 1)');
if ($currentSeverity !== '' && !in_array($currentSeverity, $severities, true)) {
    $severities[] = $currentSeverity;
}
?>
<div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/admin/regulations') ?>">Rules & Regulations</a> / <?= $isEdit ? 'Edit' : 'Add' ?></div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="editor-close" href="<?= $isEdit ? url('/admin/regulations/' . $regulation['public_id']) : url('/admin/regulations') ?>" aria-label="Close">&times;</a>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span>
        <div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div>
    </div>
<?php endif; ?>

<form class="card form-card regulation-form" method="POST" action="<?= e($action) ?>">
    <?= csrf_field() ?><?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <section class="form-section announcement-section">
        <div class="section-title">1. General policy identification</div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="reference_code">Policy code / reference <span class="req">*</span></label>
                <input class="input<?= $invalid('reference_code') ?>" id="reference_code" name="reference_code" value="<?= e($values['reference_code'] ?? '') ?>" placeholder="RULE-2026-001" required>
                <?php if ($errorFor('reference_code')): ?><div class="form-error"><?= e($errorFor('reference_code')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="category">Primary category <span class="req">*</span></label>
                <select class="input<?= $invalid('category') ?>" id="category" name="category" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>"<?= $currentCategory === $category ? ' selected' : '' ?>><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('category')): ?><div class="form-error"><?= e($errorFor('category')) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="severity">Offense severity rating <span class="req">*</span></label>
                <select class="input<?= $invalid('severity') ?>" id="severity" name="severity" required>
                    <?php foreach ($severities as $severity): ?>
                        <option value="<?= e($severity) ?>"<?= $currentSeverity === $severity ? ' selected' : '' ?>><?= e($severity) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('severity')): ?><div class="form-error"><?= e($errorFor('severity')) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="title">Full regulation title <span class="req">*</span></label>
            <input class="input<?= $invalid('title') ?>" id="title" name="title" value="<?= e($values['title'] ?? '') ?>" placeholder="Enter the full regulation title" required>
            <?php if ($errorFor('title')): ?><div class="form-error"><?= e($errorFor('title')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="summary">Executive summary <span class="req">*</span></label>
            <textarea class="input<?= $invalid('summary') ?>" id="summary" name="summary" rows="3" placeholder="Appears on mobile portal preview." required><?= e($values['summary'] ?? '') ?></textarea>
            <div class="help-text">Appears on mobile portal preview.</div>
            <?php if ($errorFor('summary')): ?><div class="form-error"><?= e($errorFor('summary')) ?></div><?php endif; ?>
        </div>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">2. Regulation provisions &amp; text editor</div>
        <div class="editor-mode">WYSIWYG mode</div>
        <div class="field">
            <label for="body">Policy content <span class="req">*</span></label>
            <div class="editor-toolbar" data-editor-toolbar data-target="body">
                <button type="button" data-editor-before="<strong>" data-editor-after="</strong>" data-editor-placeholder="bold text" title="Bold">B</button>
                <button type="button" data-editor-before="<em>" data-editor-after="</em>" data-editor-placeholder="italic text" title="Italic"><em>I</em></button>
                <button type="button" data-editor-before="<u>" data-editor-after="</u>" data-editor-placeholder="underlined text" title="Underline"><u>U</u></button>
                <span class="editor-toolbar__sep"></span>
                <button type="button" data-editor-before="<h1>" data-editor-after="</h1>" data-editor-placeholder="Heading 1" title="Heading 1">H1</button>
                <button type="button" data-editor-before="<h2>" data-editor-after="</h2>" data-editor-placeholder="Heading 2" title="Heading 2">H2</button>
                <span class="editor-toolbar__sep"></span>
                <button type="button" data-editor-before="<ul>&#10;  <li>" data-editor-after="</li>&#10;</ul>" data-editor-placeholder="List item" title="Bulleted list">&bull; List</button>
                <button type="button" data-editor-before="<ol>&#10;  <li>" data-editor-after="</li>&#10;</ol>" data-editor-placeholder="List item" title="Numbered list">1. List</button>
                <button type="button" data-editor-before="&lt;a href=&quot;https://example.com&quot;&gt;" data-editor-after="&lt;/a&gt;" data-editor-placeholder="link text" title="Link">Link</button>
                <button type="button" data-editor-before="&lt;img src=&quot;https://example.com/image.jpg&quot; alt=&quot;" data-editor-after="&quot;&gt;" data-editor-placeholder="image description" title="Image">Image</button>
            </div>
            <textarea class="input input--rich<?= $invalid('body') ?>" id="body" name="body" rows="16" placeholder="Draft official sections, sub-clauses and specific penal consequences." required><?= e($values['body'] ?? '') ?></textarea>
            <div class="help-text">Basic HTML is supported. Scripts and event attributes are removed on save.</div>
            <?php if ($errorFor('body')): ?><div class="form-error"><?= e($errorFor('body')) ?></div><?php endif; ?>
        </div>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">3. Publication &amp; governance</div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="audience">Audience</label>
                <input class="input" id="audience" name="audience" value="<?= e($values['audience'] ?? 'All Students') ?>">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select class="input" id="status" name="status">
                    <?php foreach (['Draft', 'Pending Review', 'Published', 'Archived'] as $status): ?>
                        <option value="<?= e($status) ?>"<?= ($values['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="effective_date">Effective date <span class="req">*</span></label>
                <input class="input<?= $invalid('effective_date') ?>" id="effective_date" name="effective_date" value="<?= e($values['effective_date'] ?? '') ?>" placeholder="e.g. September 15, 2025" required>
                <?php if ($errorFor('effective_date')): ?><div class="form-error"><?= e($errorFor('effective_date')) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="review_schedule">Review schedule</label>
                <input class="input" id="review_schedule" name="review_schedule" value="<?= e($values['review_schedule'] ?? '') ?>" placeholder="Annual review every June">
            </div>
            <div class="field">
                <label for="approval_body">Approving body</label>
                <input class="input" id="approval_body" name="approval_body" value="<?= e($values['approval_body'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="sanctions">Sanctions and penalties</label>
                <textarea class="input" id="sanctions" name="sanctions" rows="4" placeholder="One sanction per line"><?= e($values['sanctions'] ?? '') ?></textarea>
                <div class="help-text">Each line appears as a separate item on the student-facing policy page.</div>
            </div>
        </div>
    </section>

    <div class="form-actions form-actions--announcement">
        <a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/regulations/' . $regulation['public_id']) : url('/admin/regulations') ?>">Cancel</a>
        <button class="btn btn--outline" type="submit" name="save_action" value="draft">Save Draft</button>
        <button class="btn btn--primary" type="submit" name="save_action" value="publish">Publish Policy</button>
    </div>
</form>
