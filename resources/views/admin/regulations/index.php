<?php
/** @var list<array<string, mixed>> $regulations */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
/** @var list<string> $categories */
?><div class="page-head">
    <div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div>
    <div class="page-head__actions"><a class="btn btn--primary" href="<?= url('/admin/regulations/create') ?>"><?= App\Support\Icons::render('plus', 14) ?> Add Regulation</a></div>
</div>

<div class="stats">
    <div class="stat"><div class="stat__label">Published</div><div class="stat__value"><?= number_format($counts['published'] ?? 0) ?></div><div class="stat__sub">Enforced student policies</div></div>
    <div class="stat"><div class="stat__label">Pending Review</div><div class="stat__value"><?= number_format($counts['pending'] ?? 0) ?></div><div class="stat__sub">Awaiting approval</div></div>
    <div class="stat"><div class="stat__label">Drafts</div><div class="stat__value"><?= number_format($counts['draft'] ?? 0) ?></div><div class="stat__sub">Not yet published</div></div>
    <div class="stat"><div class="stat__label">Portal Views</div><div class="stat__value"><?= number_format($counts['views'] ?? 0) ?></div><div class="stat__sub">Student readings</div></div>
</div>

<form class="filter-bar filter-bar--3" method="GET" action="<?= url('/admin/regulations') ?>">
    <div class="field"><label for="search">Search policies</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Title, code or policy text"></span></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (['Published', 'Pending Review', 'Draft', 'Archived'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="category">Category</label><select class="input" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($filters['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/regulations') ?>">Reset</a></div>
</form>

<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Regulation Library</div><div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> policies</div></div><span class="badge badge--brand"><?= number_format($pagination['total']) ?> policies</span></div>
    <?php if ($regulations === []): ?>
        <div class="empty-state"><strong>No regulations match these filters.</strong><div>Try another search term or status.</div></div>
    <?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Policy</th><th>Category</th><th>Status</th><th>Effective</th><th>Views</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($regulations as $regulation): ?>
            <tr>
                <td><div class="identity-cell"><span class="avatar avatar--amber"><?= App\Support\Icons::render('scale', 15) ?></span><div><a class="cell-title" href="<?= url('/admin/regulations/' . $regulation['public_id']) ?>"><?= e($regulation['title']) ?></a><div class="cell-sub"><?= e($regulation['reference_code']) ?> · <?= e($regulation['public_id']) ?></div><div class="cell-sub"><?= e($regulation['approval_body']) ?></div></div></div></td>
                <td><div class="cell-title"><?= e($regulation['category']) ?></div><div class="cell-sub"><?= e($regulation['review_schedule']) ?></div></td>
                <td><span class="badge <?= e(status_class($regulation['status'])) ?>"><?= e($regulation['status']) ?></span></td>
                <td><div class="cell-title"><?= e($regulation['effective_date']) ?></div><div class="cell-sub"><?= e($regulation['audience']) ?></div></td>
                <td><?= number_format((int) $regulation['views']) ?></td>
                <td class="u-right"><div class="cell-actions"><a class="btn btn--ghost btn--sm" href="<?= url('/admin/regulations/' . $regulation['public_id']) ?>">Open</a><a class="btn btn--outline btn--sm" href="<?= url('/admin/regulations/' . $regulation['public_id'] . '/edit') ?>">Edit</a></div></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
    <?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/regulations', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' policies']) ?>
</div>
