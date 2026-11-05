<?php
/** @var array<string, mixed>|null $broadcast */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$isEdit = $broadcast !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/broadcasts/' . $broadcast['public_id']) : url('/admin/broadcasts');
?><div class="page-head">
    <div><div class="page-head__eyebrow"><a href="<?= url('/admin/broadcasts') ?>">Broadcasts</a> / <?= $isEdit ? 'Edit' : 'Compose' ?></div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div>
    <a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/broadcasts/' . $broadcast['public_id']) : url('/admin/broadcasts') ?>">Cancel</a>
</div>
<?php if ($errors !== []): ?><div class="alert alert--danger"><span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div></div><?php endif; ?>
<form class="card form-card" method="POST" action="<?= e($action) ?>">
    <?= csrf_field() ?><?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('broadcast', 14) ?> Message</div>
        <div class="form-grid">
            <div class="field"><label for="subject">Subject <span class="req">*</span></label><input class="input<?= $invalid('subject') ?>" id="subject" name="subject" value="<?= e($values['subject'] ?? '') ?>" maxlength="200" required><?php if ($errorFor('subject')): ?><div class="form-error"><?= e($errorFor('subject')) ?></div><?php endif; ?></div>
            <div class="field"><label for="message">Message <span class="req">*</span></label><textarea class="input<?= $invalid('message') ?>" id="message" name="message" rows="8" maxlength="4000" required><?= e($values['message'] ?? '') ?></textarea><?php if ($errorFor('message')): ?><div class="form-error"><?= e($errorFor('message')) ?></div><?php endif; ?></div>
        </div>
    </section>
    <section class="form-section">
        <div class="section-title"><?= App\Support\Icons::render('users', 14) ?> Audience & Delivery</div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="audience">Audience</label><select class="input" id="audience" name="audience"><?php foreach (['All Student Portals', 'Incoming Freshmen', 'Student Officers', 'Graduating Students', 'Faculty & Staff'] as $audience): ?><option value="<?= e($audience) ?>"<?= ($values['audience'] ?? '') === $audience ? ' selected' : '' ?>><?= e($audience) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="channel">Channel</label><select class="input" id="channel" name="channel"><?php foreach (['Portal Notification', 'Portal + Email', 'University Email', 'SMS Alert'] as $channel): ?><option value="<?= e($channel) ?>"<?= ($values['channel'] ?? '') === $channel ? ' selected' : '' ?>><?= e($channel) ?></option><?php endforeach; ?></select><div class="help-text">Email delivery is logged to storage/logs/mail.log in development.</div></div>
            <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><?php foreach (['Draft', 'Pending', 'Scheduled', 'Sent'] as $status): ?><option value="<?= e($status) ?>"<?= ($values['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="scheduled_at">Scheduled for</label><input class="input" id="scheduled_at" name="scheduled_at" type="datetime-local" value="<?= e(datetime_input($values['scheduled_at'] ?? '')) ?>"></div>
        </div>
    </section>
    <div class="form-actions"><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/broadcasts/' . $broadcast['public_id']) : url('/admin/broadcasts') ?>">Cancel</a><button class="btn btn--primary" type="submit"><?= $isEdit ? 'Save Broadcast' : 'Save Broadcast' ?></button></div>
</form>
