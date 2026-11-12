@extends('layouts.app')
@section('content')
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
        <div class="page-head__eyebrow"><a href="{!! url('/admin/regulations') !!}">Rules & Regulations</a> / {!! $isEdit ? 'Edit' : 'Add' !!}</div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
    <div class="page-head__actions">
        <a class="editor-close" href="{!! $isEdit ? url('/admin/regulations/' . $regulation['public_id']) : url('/admin/regulations') !!}" aria-label="Close">&times;</a>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span>
        <div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text">{{ reset($errors) }}</div></div>
    </div>
<?php endif; ?>

<form class="card form-card regulation-form" method="POST" action="{{ $action }}">
    {!! csrf_field() !!}<?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <section class="form-section announcement-section">
        <div class="section-title">1. General policy identification</div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="reference_code">Policy code / reference <span class="req">*</span></label>
                <input class="input{!! $invalid('reference_code') !!}" id="reference_code" name="reference_code" value="{{ $values['reference_code'] ?? '' }}" placeholder="RULE-2026-001" required>
                <?php if ($errorFor('reference_code')): ?><div class="form-error">{{ $errorFor('reference_code') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="category">Primary category <span class="req">*</span></label>
                <select class="input{!! $invalid('category') !!}" id="category" name="category" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="{{ $category }}"{!! $currentCategory === $category ? ' selected' : '' !!}>{{ $category }}</option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('category')): ?><div class="form-error">{{ $errorFor('category') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="severity">Offense severity rating <span class="req">*</span></label>
                <select class="input{!! $invalid('severity') !!}" id="severity" name="severity" required>
                    <?php foreach ($severities as $severity): ?>
                        <option value="{{ $severity }}"{!! $currentSeverity === $severity ? ' selected' : '' !!}>{{ $severity }}</option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errorFor('severity')): ?><div class="form-error">{{ $errorFor('severity') }}</div><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="title">Full regulation title <span class="req">*</span></label>
            <input class="input{!! $invalid('title') !!}" id="title" name="title" value="{{ $values['title'] ?? '' }}" placeholder="Enter the full regulation title" required>
            <?php if ($errorFor('title')): ?><div class="form-error">{{ $errorFor('title') }}</div><?php endif; ?>
        </div>

        <div class="field">
            <label for="summary">Executive summary <span class="req">*</span></label>
            <textarea class="input{!! $invalid('summary') !!}" id="summary" name="summary" rows="3" placeholder="Appears on mobile portal preview." required>{{ $values['summary'] ?? '' }}</textarea>
            <div class="help-text">Appears on mobile portal preview.</div>
            <?php if ($errorFor('summary')): ?><div class="form-error">{{ $errorFor('summary') }}</div><?php endif; ?>
        </div>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">2. Regulation provisions &amp; text editor</div>
        <div class="field">
            <label id="body-label" for="body">Policy content <span class="req">*</span></label>
            <div class="editor-toolbar" data-editor-toolbar data-editor-rich data-target="body" role="toolbar" aria-label="Policy content formatting">
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
            <textarea class="input input--rich{!! $invalid('body') !!}" id="body" name="body" rows="16" placeholder="Draft official sections, sub-clauses and specific penal consequences." required data-editor-input>{{ $values['body'] ?? '' }}</textarea>
            <div class="input input--rich editor-surface{!! $invalid('body') !!}" id="body-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="body-label" data-editor-surface data-target="body" data-placeholder="Draft official sections, sub-clauses and specific penal consequences." hidden></div>
            <?php if ($errorFor('body')): ?><div class="form-error">{{ $errorFor('body') }}</div><?php endif; ?>
            <div class="form-error" data-editor-error hidden>Please enter the policy content.</div>
        </div>
        <div class="field">
            <label for="sanctions">Sanctions and penalties</label>
            <textarea class="input" id="sanctions" name="sanctions" rows="4" placeholder="One sanction per line">{{ $values['sanctions'] ?? '' }}</textarea>
            <div class="help-text">Each line appears as a separate item on the student-facing policy page.</div>
        </div>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">3. Publication &amp; governance</div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="audience">Audience</label>
                <input class="input" id="audience" name="audience" value="{{ $values['audience'] ?? 'All Students' }}">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select class="input" id="status" name="status">
                    <?php foreach (['Draft', 'Pending Review', 'Published', 'Archived'] as $status): ?>
                        <option value="{{ $status }}"{!! ($values['status'] ?? '') === $status ? ' selected' : '' !!}>{{ $status }}</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="effective_date">Effective date <span class="req">*</span></label>
                <input class="input{!! $invalid('effective_date') !!}" id="effective_date" name="effective_date" value="{{ $values['effective_date'] ?? '' }}" placeholder="e.g. September 15, 2025" required>
                <?php if ($errorFor('effective_date')): ?><div class="form-error">{{ $errorFor('effective_date') }}</div><?php endif; ?>
            </div>
        </div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="review_schedule">Review schedule</label>
                <input class="input" id="review_schedule" name="review_schedule" value="{{ $values['review_schedule'] ?? '' }}" placeholder="Annual review every June">
            </div>
            <div class="field">
                <label for="approval_body">Approving body</label>
                <input class="input" id="approval_body" name="approval_body" value="{{ $values['approval_body'] ?? '' }}">
            </div>
        </div>
    </section>

    <div class="form-actions form-actions--announcement">
        <a class="btn btn--ghost" href="{!! $isEdit ? url('/admin/regulations/' . $regulation['public_id']) : url('/admin/regulations') !!}">Cancel</a>
        <button class="btn btn--outline" type="submit" name="save_action" value="draft">Save Draft</button>
        <button class="btn btn--primary" type="submit" name="save_action" value="publish">Publish Policy</button>
    </div>
</form>

@endsection
