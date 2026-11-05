<?php
/** @var array<string, mixed>|null $announcement */
/** @var array<string, mixed> $values */
/** @var list<array<string, mixed>> $attachments */
/** @var array<string, string> $errors */
$isEdit = $announcement !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/announcements/' . $announcement['public_id']) : url('/admin/announcements');
<<<<<<< HEAD
?>
<div class="page-head">
=======
?><div class="page-head">
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
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

<<<<<<< HEAD
<form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data" class="card form-card announcement-form">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <section class="form-section announcement-section">
        <div>
            <div class="section-kicker">Announcement content editor</div>
            <p class="section-note">Compose official details, format the notice and attach supporting documents.</p>
        </div>
        <div class="section-title">1. Broadcast content</div>

        <div class="field">
            <label for="title">Announcement title <span class="req">*</span></label>
            <input class="input<?= $invalid('title') ?>" id="title" name="title" value="<?= e($values['title'] ?? '') ?>" maxlength="200" required>
            <?php if ($errorFor('title')): ?><div class="form-error"><?= e($errorFor('title')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="summary">Short summary / blurb</label>
            <textarea class="input<?= $invalid('summary') ?>" id="summary" name="summary" rows="3" maxlength="1000" placeholder="Appears in push notifications and preview cards."><?= e($values['summary'] ?? '') ?></textarea>
            <div class="help-text">Appears in push notifications and preview cards.</div>
            <?php if ($errorFor('summary')): ?><div class="form-error"><?= e($errorFor('summary')) ?></div><?php endif; ?>
        </div>

        <div class="field">
            <label for="body">Body content <span class="req">*</span></label>
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
            <textarea class="input input--rich<?= $invalid('body') ?>" id="body" name="body" rows="16" required><?= e($values['body'] ?? '') ?></textarea>
            <div class="help-text">Basic HTML is supported: paragraphs, headings, bold, lists, links and images. Scripts and event attributes are removed on save.</div>
            <?php if ($errorFor('body')): ?><div class="form-error"><?= e($errorFor('body')) ?></div><?php endif; ?>
        </div>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">2. Attachments &amp; media</div>
        <p class="section-note">PDF, PNG or JPG files, up to 5 MB each. Files are stored outside the public web root.</p>

        <label class="upload-drop" for="attachment">
            <span class="upload-drop__icon"><?= App\Support\Icons::render('upload', 24) ?></span>
            <span><strong>Click to Upload</strong> or drag and drop files here</span>
            <small>PDF, PNG or JPG &middot; up to 5 MB</small>
        </label>
        <input class="upload-drop__input<?= $invalid('attachment') ?>" id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
        <?php if ($errorFor('attachment')): ?><div class="form-error"><?= e($errorFor('attachment')) ?></div><?php endif; ?>

        <?php if ($attachments !== []): ?>
            <div class="attachment-list attachment-list--rows">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="file-row">
                        <span class="file-row__icon"><?= App\Support\Icons::render('fileText', 18) ?></span>
                        <div class="file-row__body">
                            <div class="cell-title"><?= e($attachment['name'] ?? 'Attachment') ?></div>
                            <div class="cell-sub"><?= e($attachment['size'] ?? '') ?> &middot; Uploaded &middot; Complete</div>
                        </div>
                        <span class="badge badge--verified">Uploaded</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">3. Publishing settings</div>
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
        <div class="form-grid form-grid--3">
=======
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
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
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
<<<<<<< HEAD
        </div>
    </section>

    <div class="form-actions form-actions--announcement">
        <a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/announcements/' . $announcement['public_id']) : url('/admin/announcements') ?>">Cancel</a>
        <button class="btn btn--outline" type="submit" name="save_action" value="draft">Add to Drafts</button>
        <button class="btn btn--primary" type="submit" name="save_action" value="save">Save Changes</button>
=======
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
        <button class="btn btn--primary" type="submit"><?= $isEdit ? 'Create Announcement' : 'Save Changes' ?></button>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    </div>
</form>
