<?php
/** @var array<string, mixed>|null $module */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$isEdit = $module !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/orientation/' . $module['public_id']) : url('/admin/orientation');
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="<?= url('/admin/orientation') ?>">Orientation Content</a> / <?= $isEdit ? 'Edit' : 'Add' ?></div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/orientation/' . $module['public_id']) : url('/admin/orientation') ?>">Cancel</a></div>
<?php if ($errors !== []): ?><div class="alert alert--danger"><span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div></div><?php endif; ?>
<form class="card form-card" method="POST" action="<?= e($action) ?>">
    <?= csrf_field() ?><?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('graduation', 14) ?> Module Details</div>
        <div class="form-grid">
            <div class="field"><label for="title">Title <span class="req">*</span></label><input class="input<?= $invalid('title') ?>" id="title" name="title" value="<?= e($values['title'] ?? '') ?>" required></div>
            <div class="form-grid form-grid--3">
                <div class="field"><label for="category">Category</label><input class="input" id="category" name="category" list="orientation-categories" value="<?= e($values['category'] ?? '') ?>" required><datalist id="orientation-categories"><?php foreach (['Campus Life', 'Academic Support', 'Health & Wellness', 'Student Services', 'Safety & Conduct', 'Financial Literacy'] as $category): ?><option value="<?= e($category) ?>"><?php endforeach; ?></datalist></div>
                <div class="field"><label for="audience">Audience</label><input class="input" id="audience" name="audience" value="<?= e($values['audience'] ?? 'Incoming Freshmen') ?>"></div>
                <div class="field"><label for="department">Department</label><input class="input" id="department" name="department" value="<?= e($values['department'] ?? '') ?>"></div>
            </div>
            <div class="form-grid form-grid--3">
                <div class="field"><label for="version">Version</label><input class="input" id="version" name="version" value="<?= e($values['version'] ?? 'v1.0') ?>"></div>
                <div class="field"><label for="duration_minutes">Duration (minutes)</label><input class="input" id="duration_minutes" name="duration_minutes" type="number" min="1" max="600" value="<?= e($values['duration_minutes'] ?? 15) ?>"></div>
                <div class="field"><label for="attachments_count">Attachment count</label><input class="input" id="attachments_count" name="attachments_count" type="number" min="0" max="100" value="<?= e($values['attachments_count'] ?? 0) ?>"></div>
            </div>
            <div class="field"><label class="checkbox"><input type="checkbox" name="mandatory" value="1"<?= !empty($values['mandatory']) ? ' checked' : '' ?>><span>Mandatory module</span></label></div>
            <div class="field"><label for="description">Short description</label><textarea class="input" id="description" name="description" rows="2"><?= e($values['description'] ?? '') ?></textarea></div>
            <div class="field"><label for="objectives">Learning objectives</label><textarea class="input" id="objectives" name="objectives" rows="4" placeholder="One objective per line"><?= e($values['objectives'] ?? '') ?></textarea></div>
            <div class="field"><label for="body">Module content <span class="req">*</span></label><textarea class="input input--rich" id="body" name="body" rows="14" required><?= e($values['body'] ?? '') ?></textarea><div class="help-text">Basic HTML is supported. Scripts and event attributes are removed on save.</div></div>
        </div>
    </section>
    <section class="form-section"><div class="section-title"><?= App\Support\Icons::render('eye', 14) ?> Publishing</div><div class="form-grid form-grid--2"><div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><?php foreach (['Draft', 'Published', 'Archived'] as $status): ?><option value="<?= e($status) ?>"<?= ($values['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div></div></section>
    <div class="form-actions"><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/orientation/' . $module['public_id']) : url('/admin/orientation') ?>">Cancel</a><button class="btn btn--primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Module' ?></button></div>
</form>
