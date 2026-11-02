<?php
/** @var array<string, mixed>|null $item */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$isEdit = $item !== null;
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$action = $isEdit ? url('/admin/lost-found/' . $item['public_id']) : url('/admin/lost-found');
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="<?= url('/admin/lost-found') ?>">Lost & Found</a> / <?= $isEdit ? 'Edit' : 'Post' ?></div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/lost-found/' . $item['public_id']) : url('/admin/lost-found') ?>">Cancel</a></div>
<?php if ($errors !== []): ?><div class="alert alert--danger"><span class="alert__ico"><?= App\Support\Icons::render('alert', 18) ?></span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text"><?= e(reset($errors)) ?></div></div></div><?php endif; ?>
<form class="card form-card" method="POST" action="<?= e($action) ?>">
    <?= csrf_field() ?><?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <section class="form-section"><div class="section-title"><?= App\Support\Icons::render('search', 14) ?> Item Details</div>
        <div class="form-grid">
            <div class="form-grid form-grid--3">
                <div class="field"><label for="type">Type</label><select class="input" id="type" name="type"><?php foreach (['Lost', 'Found'] as $type): ?><option value="<?= e($type) ?>"<?= ($values['type'] ?? '') === $type ? ' selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="category">Category</label><input class="input" id="category" name="category" list="item-categories" value="<?= e($values['category'] ?? '') ?>" required><datalist id="item-categories"><?php foreach (['Bags & Wallets', 'Electronics', 'IDs & Documents', 'Books & School Supplies', 'Clothing', 'Keys', 'Jewelry', 'Others'] as $category): ?><option value="<?= e($category) ?>"><?php endforeach; ?></datalist></div>
                <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><?php foreach (['Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed'] as $status): ?><option value="<?= e($status) ?>"<?= ($values['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="field"><label for="title">Item title <span class="req">*</span></label><input class="input<?= $invalid('title') ?>" id="title" name="title" value="<?= e($values['title'] ?? '') ?>" required></div>
            <div class="field"><label for="description">Description</label><textarea class="input" id="description" name="description" rows="5"><?= e($values['description'] ?? '') ?></textarea></div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="location">Location</label><input class="input" id="location" name="location" value="<?= e($values['location'] ?? '') ?>" required></div>
                <div class="field"><label for="date_occurred">Date and time</label><input class="input" id="date_occurred" name="date_occurred" type="datetime-local" value="<?= e($values['date_occurred'] ?? '') ?>"></div>
            </div>
            <div class="form-grid form-grid--3">
                <div class="field"><label for="reported_by">Reported by</label><input class="input" id="reported_by" name="reported_by" value="<?= e($values['reported_by'] ?? '') ?>" required></div>
                <div class="field"><label for="reporter_email">Reporter email</label><input class="input" id="reporter_email" name="reporter_email" type="email" value="<?= e($values['reporter_email'] ?? '') ?>" required></div>
                <div class="field"><label for="student_id">Student ID</label><input class="input" id="student_id" name="student_id" value="<?= e($values['student_id'] ?? '') ?>"></div>
            </div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="contact">Contact</label><input class="input" id="contact" name="contact" value="<?= e($values['contact'] ?? '') ?>"></div>
                <div class="field"><label for="custody">Custody</label><input class="input" id="custody" name="custody" value="<?= e($values['custody'] ?? 'OSAS Office') ?>"></div>
            </div>
            <div class="field"><label for="attachment">Attachment reference</label><input class="input" id="attachment" name="attachment" value="<?= e($values['attachment'] ?? '') ?>" placeholder="Optional file name or reference"></div>
        </div>
    </section>
    <div class="form-actions"><a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/lost-found/' . $item['public_id']) : url('/admin/lost-found') ?>">Cancel</a><button class="btn btn--primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Post Item' ?></button></div>
</form>
