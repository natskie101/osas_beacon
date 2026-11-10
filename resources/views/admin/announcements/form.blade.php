@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed>|null $announcement */
/** @var array<string, mixed> $values */
/** @var list<array<string, mixed>> $attachments */
/** @var array<string, string> $errors */
$isEdit = $announcement !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/announcements/' . $announcement['public_id']) : url('/admin/announcements');
?>
<div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="{!! url('/admin/announcements') !!}">Announcements</a> / {!! $isEdit ? 'Edit' : 'Create' !!}</div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="{!! $isEdit ? url('/admin/announcements/' . $announcement['public_id']) : url('/admin/announcements') !!}">Cancel</a>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span>
        <div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text">{{ reset($errors) }}</div></div>
    </div>
<?php endif; ?>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card form-card announcement-form">
    {!! csrf_field() !!}
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <section class="form-section announcement-section">
        <div>
            <div class="section-kicker">Announcement content editor</div>
            <p class="section-note">Compose official details, format the notice and attach supporting documents.</p>
        </div>
        <div class="section-title">1. Broadcast content</div>

        <div class="field">
            <label for="title">Announcement title <span class="req">*</span></label>
            <input class="input{!! $invalid('title') !!}" id="title" name="title" value="{{ $values['title'] ?? '' }}" maxlength="200" required>
            <?php if ($errorFor('title')): ?><div class="form-error">{{ $errorFor('title') }}</div><?php endif; ?>
        </div>

        <div class="field">
            <label for="summary">Short summary / blurb</label>
            <textarea class="input{!! $invalid('summary') !!}" id="summary" name="summary" rows="3" maxlength="1000" placeholder="Appears in push notifications and preview cards.">{{ $values['summary'] ?? '' }}</textarea>
            <div class="help-text">Appears in push notifications and preview cards.</div>
            <?php if ($errorFor('summary')): ?><div class="form-error">{{ $errorFor('summary') }}</div><?php endif; ?>
        </div>

        <div class="field">
            <label id="body-label" for="body">Body content <span class="req">*</span></label>
            <div class="editor-toolbar" data-editor-toolbar data-editor-rich data-target="body" role="toolbar" aria-label="Body formatting">
                <button type="button" data-editor-command="bold" title="Bold"><strong>B</strong></button>
                <button type="button" data-editor-command="italic" title="Italic"><em>I</em></button>
                <button type="button" data-editor-command="underline" title="Underline"><u>U</u></button>
                <span class="editor-toolbar__sep"></span>
                <button type="button" data-editor-command="formatBlock" data-editor-value="h1" title="Heading 1">H1</button>
                <button type="button" data-editor-command="formatBlock" data-editor-value="h2" title="Heading 2">H2</button>
                <button type="button" data-editor-command="formatBlock" data-editor-value="p" title="Paragraph">&#182;</button>
                <span class="editor-toolbar__sep"></span>
                <button type="button" data-editor-command="insertUnorderedList" title="Bulleted list">&bull; List</button>
                <button type="button" data-editor-command="insertOrderedList" title="Numbered list">1. List</button>
                <button type="button" data-editor-command="createLink" title="Insert link">Link</button>
                <button type="button" data-editor-command="insertImage" title="Insert image">Image</button>
            </div>
            <textarea class="input input--rich{!! $invalid('body') !!}" id="body" name="body" rows="16" required data-editor-input>{{ $values['body'] ?? '' }}</textarea>
            <div class="input input--rich editor-surface{!! $invalid('body') !!}" id="body-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="body-label" data-editor-surface data-target="body" data-placeholder="Write the announcement content here…" hidden></div>
            <?php if ($errorFor('body')): ?><div class="form-error">{{ $errorFor('body') }}</div><?php endif; ?>
            <div class="form-error" data-editor-error hidden>Please enter the announcement body.</div>
        </div>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">2. Attachments &amp; media</div>
        <p class="section-note">PDF, PNG or JPG files, up to 5 MB each. Files are stored outside the public web root.</p>

        <label class="upload-drop" for="attachment">
            <span class="upload-drop__icon">{!! App\Support\Icons::render('upload', 24) !!}</span>
            <span data-upload-prompt><strong>Click to Upload</strong> or drag and drop files here</span>
            <small data-upload-prompt>PDF, PNG or JPG &middot; up to 5 MB</small>
            <span class="upload-drop__filename" data-upload-filename aria-live="polite" hidden></span>
        </label>
        <input class="upload-drop__input{!! $invalid('attachment') !!}" id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
        <?php if ($errorFor('attachment')): ?><div class="form-error">{{ $errorFor('attachment') }}</div><?php endif; ?>

        <?php if ($attachments !== []): ?>
            <div class="attachment-list attachment-list--rows">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="file-row">
                        <span class="file-row__icon">{!! App\Support\Icons::render('fileText', 18) !!}</span>
                        <div class="file-row__body">
                            <div class="cell-title">{{ $attachment['name'] ?? 'Attachment' }}</div>
                            <div class="cell-sub">{{ $attachment['size'] ?? '' }} &middot; Uploaded &middot; Complete</div>
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
                <input class="input{!! $invalid('category') !!}" id="category" name="category" list="announcement-categories" value="{{ $values['category'] ?? '' }}" required>
                <datalist id="announcement-categories">
                    <?php foreach (['Registrar & Enrollment', 'Scholarships & Financial Aid', 'Student Affairs', 'Academic Affairs', 'Health & Wellness', 'Campus Life', 'Safety & Security'] as $category): ?><option value="{{ $category }}"><?php endforeach; ?>
                </datalist>
                <?php if ($errorFor('category')): ?><div class="form-error">{{ $errorFor('category') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="audience">Audience <span class="req">*</span></label>
                <select class="input{!! $invalid('audience') !!}" id="audience" name="audience">
                    <?php foreach (['All Student Portals', 'Incoming Freshmen', 'Student Officers', 'Faculty & Staff', 'Graduating Students'] as $audience): ?>
                        <option value="{{ $audience }}"{!! ($values['audience'] ?? '') === $audience ? ' selected' : '' !!}>{{ $audience }}</option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('audience')): ?><div class="form-error">{{ $errorFor('audience') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="priority">Priority</label>
                <select class="input" id="priority" name="priority">
                    <?php foreach (['Standard Priority', 'Information', 'High Priority', 'Urgent'] as $priority): ?>
                        <option value="{{ $priority }}"{!! ($values['priority'] ?? '') === $priority ? ' selected' : '' !!}>{{ $priority }}</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="status">Status</label>
                <select class="input" id="status" name="status">
                    <?php foreach (['Draft', 'Scheduled', 'Published', 'Archived'] as $status): ?>
                        <option value="{{ $status }}"{!! ($values['status'] ?? '') === $status ? ' selected' : '' !!}>{{ $status }}</option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">Publishing sends a notification to the selected student audience.</div>
            </div>
            <div class="field">
                <label for="scheduled_for">Schedule date and time</label>
                <input class="input{!! $invalid('scheduled_for') !!}" id="scheduled_for" name="scheduled_for" type="datetime-local" value="{{ datetime_input($values['scheduled_for'] ?? '') }}">
                <?php if ($errorFor('scheduled_for')): ?><div class="form-error">{{ $errorFor('scheduled_for') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="author">Displayed author</label>
                <input class="input{!! $invalid('author') !!}" id="author" name="author" value="{{ $values['author'] ?? 'OSAS Administration' }}" maxlength="150">
                <?php if ($errorFor('author')): ?><div class="form-error">{{ $errorFor('author') }}</div><?php endif; ?>
            </div>
        </div>
    </section>

    <div class="form-actions form-actions--announcement">
        <a class="btn btn--ghost" href="{!! $isEdit ? url('/admin/announcements/' . $announcement['public_id']) : url('/admin/announcements') !!}">Cancel</a>
        <button class="btn btn--outline" type="submit" name="save_action" value="draft">Add to Drafts</button>
        <button class="btn btn--primary" type="submit" name="save_action" value="save" data-confirm="Save changes to this announcement?">Save Changes</button>
    </div>
</form>

@endsection
