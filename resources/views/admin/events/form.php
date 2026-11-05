<?php
/** @var array<string, mixed>|null $event */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$isEdit = $event !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/events/' . $event['public_id']) : url('/admin/events');
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="<?= url('/admin/events') ?>">Events & Activities</a> / <?= $isEdit ? 'Edit' : 'Create' ?></div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/events/' . $event['public_id']) : url('/admin/events') ?>">Cancel</a></div>
<?php if ($errors !== []): ?><div class="alert alert--danger"><span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div></div><?php endif; ?>
<form class="card form-card" method="POST" action="<?= e($action) ?>">
    <?= csrf_field() ?><?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <section class="form-section"><div class="section-title"><?= App\Support\Icons::render('calendar', 14) ?> Event Details</div>
        <div class="form-grid">
            <div class="field"><label for="title">Event title <span class="req">*</span></label><input class="input<?= $invalid('title') ?>" id="title" name="title" value="<?= e($values['title'] ?? '') ?>" required></div>
            <div class="form-grid form-grid--3">
                <div class="field"><label for="category">Category</label><input class="input" id="category" name="category" list="event-categories" value="<?= e($values['category'] ?? '') ?>" required><datalist id="event-categories"><?php foreach (['Student Life', 'Seminar', 'Sports', 'Cultural', 'Career', 'Community Service', 'Orientation'] as $category): ?><option value="<?= e($category) ?>"><?php endforeach; ?></datalist></div>
                <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><?php foreach (['Scheduled', 'Ongoing', 'Completed', 'Cancelled'] as $status): ?><option value="<?= e($status) ?>"<?= ($values['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="mode">Mode</label><select class="input" id="mode" name="mode"><?php foreach (['On-campus', 'Online', 'Hybrid', 'Off-campus'] as $mode): ?><option value="<?= e($mode) ?>"<?= ($values['mode'] ?? '') === $mode ? ' selected' : '' ?>><?= e($mode) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="start_at">Start date and time <span class="req">*</span></label><input class="input<?= $invalid('start_at') ?>" id="start_at" name="start_at" type="datetime-local" value="<?= e($values['start_at'] ?? '') ?>" required></div>
                <div class="field"><label for="end_at">End date and time</label><input class="input<?= $invalid('end_at') ?>" id="end_at" name="end_at" type="datetime-local" value="<?= e($values['end_at'] ?? '') ?>"></div>
            </div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="venue">Venue</label><input class="input" id="venue" name="venue" value="<?= e($values['venue'] ?? '') ?>" required></div>
                <div class="field"><label for="audience">Audience</label><input class="input" id="audience" name="audience" value="<?= e($values['audience'] ?? 'All Students') ?>"></div>
            </div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="capacity">Capacity</label><input class="input" id="capacity" name="capacity" type="number" min="0" value="<?= e($values['capacity'] ?? 100) ?>"></div>
                <div class="field"><label for="registered">Registered</label><input class="input" id="registered" name="registered" type="number" min="0" value="<?= e($values['registered'] ?? 0) ?>"><div class="help-text">Kept for reporting; event registration is not enabled in the portal.</div></div>
            </div>
            <div class="field"><label for="description">Description</label><textarea class="input" id="description" name="description" rows="5"><?= e($values['description'] ?? '') ?></textarea></div>
        </div>
    </section>
    <section class="form-section"><div class="section-title"><?= App\Support\Icons::render('shield', 14) ?> Organizer</div><div class="form-grid form-grid--2"><div class="field"><label for="organizer">Organizer</label><input class="input" id="organizer" name="organizer" value="<?= e($values['organizer'] ?? '') ?>"></div><div class="field"><label for="verification">Verification note</label><input class="input" id="verification" name="verification" value="<?= e($values['verification'] ?? '') ?>"></div></div></section>
    <div class="form-actions"><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/events/' . $event['public_id']) : url('/admin/events') ?>">Cancel</a><button class="btn btn--primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Event' ?></button></div>
</form>
