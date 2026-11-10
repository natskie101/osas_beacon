@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed>|null $module */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $mediaFile */
$isEdit = $module !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/orientation/' . $module['public_id']) : url('/admin/orientation');
$departments = config('people.departments', []);
$contentTypes = ['File', 'Link'];
$currentContentType = (string) ($values['content_type'] ?? 'File');
$currentContentType = in_array($currentContentType, ['File', 'Link'], true) ? $currentContentType : 'File';
$linkActive = $currentContentType === 'Link';
$verificationMethods = [
    'Quiz Assessment' => 'Attach Quiz Assessment (Passing score required)',
    'Digital Acknowledgement' => 'Digital Acknowledgement / Sign-off only',
];
$currentVerification = (string) ($values['verification_method'] ?? 'Quiz Assessment');
$currentDepartment = (string) ($values['department'] ?? '');
$moduleCode = $isEdit ? (string) ($module['public_id'] ?? '') : 'Auto-generated';
?>
<div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="{!! url('/admin/orientation') !!}">Orientation Content</a> / {!! $isEdit ? 'Edit' : 'Create' !!}</div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
    <div class="page-head__actions">
        <?php if ($isEdit && ($values['verification_method'] ?? '') === 'Quiz Assessment'): ?>
            <a class="btn btn--outline" href="{!! url('/admin/orientation/' . $module['public_id'] . '/quiz') !!}">Manage Quiz</a>
        <?php endif; ?>
        <a class="editor-close" href="{!! $isEdit ? url('/admin/orientation/' . $module['public_id']) : url('/admin/orientation') !!}" aria-label="Close">&times;</a>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span>
        <div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text">{{ reset($errors) }}</div></div>
    </div>
<?php endif; ?>

<form class="card form-card orientation-form" method="POST" action="{{ $action }}" enctype="multipart/form-data">
    {!! csrf_field() !!}<?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <input type="hidden" name="attachments_count" value="{{ $values['attachments_count'] ?? 0 }}">

    <section class="form-section announcement-section">
        <div class="section-title">1. Module definition</div>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="module_code">Module code</label>
                <input class="input" id="module_code" value="{{ $moduleCode }}" readonly>
            </div>
            <div class="field">
                <label for="requirement_level">Requirement level <span class="req">*</span></label>
                <select class="input" id="requirement_level" name="mandatory">
                    <option value="1"{!! !empty($values['mandatory']) ? ' selected' : '' !!}>Mandatory (Required for Clearance)</option>
                    <option value="0"{!! empty($values['mandatory']) ? ' selected' : '' !!}>Optional (Recommended)</option>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="title">Module title <span class="req">*</span></label>
            <input class="input{!! $invalid('title') !!}" id="title" name="title" value="{{ $values['title'] ?? '' }}" maxlength="200" placeholder="Enter the module title" required>
            <?php if ($errorFor('title')): ?><div class="form-error">{{ $errorFor('title') }}</div><?php endif; ?>
        </div>

        <div class="field">
            <label for="description">Summary / overview description <span class="req">*</span></label>
            <textarea class="input{!! $invalid('description') !!}" id="description" name="description" rows="3" placeholder="Summary shown to freshmen in the module list." required>{{ $values['description'] ?? '' }}</textarea>
            <?php if ($errorFor('description')): ?><div class="form-error">{{ $errorFor('description') }}</div><?php endif; ?>
        </div>

        <div class="field">
            <label for="objectives">Learning objectives</label>
            <textarea class="input" id="objectives" name="objectives" rows="3" placeholder="One objective per line">{{ $values['objectives'] ?? '' }}</textarea>
        </div>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">2. Content type &amp; media attachments</div>
        <div class="type-tabs" role="radiogroup" aria-label="Content type" data-content-toggle>
            <?php foreach ($contentTypes as $type): ?>
                <label class="type-tab{!! $currentContentType === $type ? ' type-tab--on' : '' !!}" data-content-value="{{ $type }}">
                    <input type="radio" name="content_type" value="{{ $type }}"{!! $currentContentType === $type ? ' checked' : '' !!}>
                    {{ $type }}
                </label>
            <?php endforeach; ?>
        </div>

        <div data-content-panel="File"{!! $linkActive ? ' hidden' : '' !!}>
            <label class="upload-drop" for="media">
                <span class="upload-drop__icon">{!! App\Support\Icons::render('upload', 24) !!}</span>
                <span data-upload-prompt><strong>Click to upload document or local media</strong> or drag and drop</span>
                <small data-upload-prompt>MP4, MOV, PDF or DOCX &middot; up to 250 MB</small>
                <span class="upload-drop__filename" data-upload-filename aria-live="polite" hidden></span>
            </label>
            <input class="upload-drop__input" id="media" name="media" type="file" accept=".mp4,.mov,.pdf,.docx,video/mp4,video/quicktime,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
            <?php if ($errorFor('media')): ?><div class="form-error">{{ $errorFor('media') }}</div><?php endif; ?>

            <?php if ($mediaFile !== null): ?>
                <div class="attachment-list attachment-list--rows">
                    <div class="file-row">
                        <span class="file-row__icon">{!! App\Support\Icons::render('fileText', 18) !!}</span>
                        <div class="file-row__body">
                            <div class="cell-title">{{ $mediaFile['original_name'] ?? 'Module media' }}</div>
                            <div class="cell-sub">{{ format_bytes((int) ($mediaFile['size_bytes'] ?? 0)) }} &middot; Uploaded &middot; Complete</div>
                        </div>
                        <a class="btn btn--ghost btn--sm" href="{!! url('/admin/orientation/' . ($module['public_id'] ?? '') . '/media') !!}">{!! App\Support\Icons::render('download', 13) !!} Download</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div data-content-panel="Link"{!! $linkActive ? '' : ' hidden' !!}>
            <div class="field">
                <label for="video_url">Google Drive video link</label>
                <input class="input{!! $invalid('video_url') !!}" id="video_url" name="video_url" value="{{ $values['video_url'] ?? '' }}" maxlength="500" placeholder="Paste a Google Drive video link">
                <div class="help-text">Paste the full video link. For Google Drive, set sharing access so students can view the file. Students will watch it inside the portal.</div>
                <?php if ($errorFor('video_url')): ?><div class="form-error">{{ $errorFor('video_url') }}</div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section announcement-section">
        <div class="section-title">3. Target audience &amp; completion settings</div>
        <div class="field">
            <label>Completion verification method</label>
            <div class="radio-list">
                <?php foreach ($verificationMethods as $method => $label): ?>
                    <label class="radio-pill{!! $currentVerification === $method ? ' radio-pill--on' : '' !!}">
                        <input type="radio" name="verification_method" value="{{ $method }}"{!! $currentVerification === $method ? ' checked' : '' !!}>
                        {{ $label }}
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($errorFor('verification_method')): ?><div class="form-error">{{ $errorFor('verification_method') }}</div><?php endif; ?>
        </div>

        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="compliance_deadline">Compliance deadline</label>
                <input class="input{!! $invalid('compliance_deadline') !!}" id="compliance_deadline" name="compliance_deadline" type="date" value="{{ $values['compliance_deadline'] ?? '' }}">
                <?php if ($errorFor('compliance_deadline')): ?><div class="form-error">{{ $errorFor('compliance_deadline') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="department">Publishing department</label>
                <select class="input" id="department" name="department">
                    <option value="">Select a department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="{{ $department }}"{!! $currentDepartment === $department ? ' selected' : '' !!}>{{ $department }}</option>
                    <?php endforeach; ?>
                    <?php if ($currentDepartment !== '' && !in_array($currentDepartment, $departments, true)): ?>
                        <option value="{{ $currentDepartment }}" selected>{{ $currentDepartment }}</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
    </section>

    <?php if (!$isEdit): ?>
        <section class="form-section announcement-section" data-quiz-builder{!! $currentVerification === 'Quiz Assessment' ? '' : ' hidden' !!}>
            <div class="section-title">Quiz Assessment (optional)</div>
            <div class="form-grid form-grid--3">
                <div class="field">
                    <label for="quiz-title">Quiz title</label>
                    <input class="input" id="quiz-title" name="quiz[title]" maxlength="200" placeholder="Orientation Readiness Assessment">
                </div>
                <div class="field">
                    <label for="quiz-time">Time limit (minutes)</label>
                    <input class="input" id="quiz-time" name="quiz[time_limit_minutes]" type="number" min="1" max="240" value="20">
                </div>
                <div class="field">
                    <label for="quiz-status">Quiz status</label>
                    <select class="input" id="quiz-status" name="quiz[status]">
                        <option value="Draft">Draft</option>
                        <option value="Published">Published</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="quiz-instructions">Instructions</label>
                <textarea class="input" id="quiz-instructions" name="quiz[instructions]" rows="3" placeholder="Answer all questions. You need to reach the passing score to complete the module."></textarea>
            </div>

            <div class="section-title">Questions</div>
            <div data-question-list></div>
            <button type="button" class="btn btn--outline" data-add-question>Add Question</button>
        </section>

        <template id="quiz-question-template">
            <div class="quiz-question" data-question>
                <div class="quiz-question__head">
                    <strong>New Question</strong>
                    <button type="button" class="btn btn--ghost btn--sm" data-remove-question>Remove</button>
                </div>
                @include('admin/orientation/partials/question-fields', ['question' => null, 'prefix' => 'q-__INDEX__', 'fieldPrefix' => 'questions[__INDEX__]'])
            </div>
        </template>
    <?php endif; ?>

    <section class="form-section announcement-section">
        <div class="section-title">4. Module content &amp; delivery settings</div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="category">Category <span class="req">*</span></label>
                <input class="input{!! $invalid('category') !!}" id="category" name="category" list="orientation-categories" value="{{ $values['category'] ?? '' }}" required>
                <datalist id="orientation-categories">
                    <?php foreach (['Campus Life', 'Academic Support', 'Health & Wellness', 'Student Services', 'Safety & Conduct', 'Financial Literacy'] as $category): ?><option value="{{ $category }}"><?php endforeach; ?>
                </datalist>
                <?php if ($errorFor('category')): ?><div class="form-error">{{ $errorFor('category') }}</div><?php endif; ?>
            </div>
            <div class="field">
                <label for="audience">Audience</label>
                <input class="input" id="audience" name="audience" value="{{ $values['audience'] ?? 'Incoming Freshmen' }}">
            </div>
            <div class="field">
                <label for="version">Version</label>
                <input class="input" id="version" name="version" value="{{ $values['version'] ?? 'v1.0' }}">
            </div>
        </div>
        <div class="form-grid form-grid--3">
            <div class="field">
                <label for="duration_minutes">Duration (minutes)</label>
                <input class="input" id="duration_minutes" name="duration_minutes" type="number" min="1" max="600" value="{{ $values['duration_minutes'] ?? 15 }}">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select class="input" id="status" name="status">
                    <?php foreach (['Draft', 'Published', 'Archived'] as $status): ?>
                        <option value="{{ $status }}"{!! ($values['status'] ?? '') === $status ? ' selected' : '' !!}>{{ $status }}</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

    </section>

    <div class="form-actions form-actions--announcement">
        <a class="btn btn--ghost" href="{!! $isEdit ? url('/admin/orientation/' . $module['public_id']) : url('/admin/orientation') !!}">Cancel</a>
        <button class="btn btn--outline" type="submit" name="save_action" value="draft">Save as Draft</button>
        <button class="btn btn--primary" type="submit" name="save_action" value="publish">Publish Module</button>
    </div>
</form>

@endsection
