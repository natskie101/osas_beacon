<?php
/** @var list<array<string, mixed>> $modules */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, mixed> $counts */
/** @var list<string> $categories */
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--primary" href="<?= url('/admin/orientation/create') ?>"><?= App\Support\Icons::render('plus', 14) ?> Add Module</a></div>
<div class="stats">
    <div class="stat"><div class="stat__label">Published Modules</div><div class="stat__value"><?= number_format($counts['published'] ?? 0) ?></div><div class="stat__sub">Available to students</div></div>
    <div class="stat"><div class="stat__label">Completion Rate</div><div class="stat__value"><?= number_format((float) ($counts['rate'] ?? 0), 1) ?>%</div><div class="stat__sub">Average across modules</div></div>
    <div class="stat"><div class="stat__label">Learners Reached</div><div class="stat__value"><?= number_format($counts['learners'] ?? 0) ?></div><div class="stat__sub">Total module enrollments</div></div>
    <div class="stat"><div class="stat__label">Completions</div><div class="stat__value"><?= number_format($counts['completions'] ?? 0) ?></div><div class="stat__sub">Modules finished</div></div>
</div>
<form class="filter-bar filter-bar--3" method="GET" action="<?= url('/admin/orientation') ?>">
    <div class="field"><label for="search">Search modules</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Title, category or content"></span></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (['Published', 'Draft', 'Archived'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="category">Category</label><select class="input" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($filters['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/orientation') ?>">Reset</a></div>
</form>
<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Orientation Modules</div><div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> modules</div></div><span class="badge badge--brand"><?= number_format($pagination['total']) ?> modules</span></div>
    <?php if ($modules === []): ?><div class="empty-state"><strong>No modules match these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Module</th><th>Category</th><th>Status</th><th>Learners</th><th>Completion</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($modules as $module): ?>
            <tr>
                <td><div class="identity-cell"><span class="avatar avatar--green"><?= App\Support\Icons::render('graduation', 15) ?></span><div><a class="cell-title" href="<?= url('/admin/orientation/' . $module['public_id']) ?>"><?= e($module['title']) ?></a><div class="cell-sub"><?= e($module['public_id']) ?> · <?= e($module['version']) ?> · <?= (int) $module['duration_minutes'] ?> min</div><div class="cell-sub"><?= $module['mandatory'] ? 'Mandatory' : 'Optional' ?> · <?= e($module['audience']) ?></div></div></div></td>
                <td><?= e($module['category']) ?></td>
                <td><span class="badge <?= e(status_class($module['status'])) ?>"><?= e($module['status']) ?></span></td>
                <td><?= number_format((int) $module['learners']) ?></td>
                <td><div class="cell-title"><?= number_format((float) $module['completion_rate'], 1) ?>%</div><div class="progress-bar"><span style="width: <?= min(100, (float) $module['completion_rate']) ?>%"></span></div></td>
                <td class="u-right"><div class="cell-actions"><a class="btn btn--ghost btn--sm" href="<?= url('/admin/orientation/' . $module['public_id']) ?>">Open</a><a class="btn btn--outline btn--sm" href="<?= url('/admin/orientation/' . $module['public_id'] . '/edit') ?>">Edit</a></div></td>
            </tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/orientation', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' modules']) ?>
</div>
