<?php
/** @var list<array<string, mixed>> $reports */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
/** @var array<string, array{label:string,category:string,description:string}> $types */
?><div class="page-head">
    <div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div>
</div>

<div class="stats">
    <div class="stat"><div class="stat__label">Reports Generated</div><div class="stat__value"><?= number_format($counts['total'] ?? 0) ?></div><div class="stat__sub">All report records</div></div>
    <div class="stat"><div class="stat__label">Completed</div><div class="stat__value"><?= number_format($counts['completed'] ?? 0) ?></div><div class="stat__sub">Ready to export</div></div>
    <div class="stat"><div class="stat__label">Processing</div><div class="stat__value"><?= number_format($counts['processing'] ?? 0) ?></div><div class="stat__sub">Still being prepared</div></div>
    <div class="stat"><div class="stat__label">Records Covered</div><div class="stat__value"><?= number_format($counts['records'] ?? 0) ?></div><div class="stat__sub">Across all generated reports</div></div>
</div>

<div class="dash-grid">
    <section class="card panel-card">
        <div class="panel-card__head"><div><div class="panel-title">Generate a Report</div><p class="panel-card__sub">Pick a dataset and optional date range. The server builds CSV and printable HTML from live records.</p></div><?= App\Support\Icons::render('fileText', 20) ?></div>
        <form method="POST" action="<?= url('/admin/reports/generate') ?>" class="form-grid">
            <?= csrf_field() ?>
            <div class="field"><label for="report_type">Report type</label><select class="input" id="report_type" name="report_type" required><?php foreach ($types as $key => $type): ?><option value="<?= e($key) ?>"><?= e($type['label']) ?></option><?php endforeach; ?></select></div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="date_from">From</label><input class="input" id="date_from" name="date_from" type="date"></div>
                <div class="field"><label for="date_to">To</label><input class="input" id="date_to" name="date_to" type="date"></div>
            </div>
            <button class="btn btn--primary" type="submit"><?= App\Support\Icons::render('download', 14) ?> Generate Report</button>
        </form>
    </section>
    <section class="card panel-card">
        <div class="panel-card__head"><div><div class="panel-title">Available Datasets</div></div><span class="badge badge--brand"><?= count($types) ?> types</span></div>
        <div class="report-types">
            <?php foreach ($types as $type): ?>
                <div class="report-type"><strong><?= e($type['label']) ?></strong><span><?= e($type['description']) ?></span><em><?= e($type['category']) ?></em></div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<form class="filter-bar filter-bar--2" method="GET" action="<?= url('/admin/reports') ?>">
    <div class="field"><label for="search">Search reports</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Title, category or reference"></span></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (['Completed', 'Processing', 'Draft'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/reports') ?>">Reset</a></div>
</form>

<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Report Library</div><div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> reports</div></div><span class="badge badge--brand"><?= number_format($pagination['total']) ?> reports</span></div>
    <?php if ($reports === []): ?><div class="empty-state"><strong>No reports match these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Report</th><th>Period</th><th>Status</th><th>Records</th><th>Generated</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($reports as $report): ?>
            <tr><td><div class="identity-cell"><span class="avatar avatar--blue"><?= App\Support\Icons::render('fileText', 15) ?></span><div><a class="cell-title" href="<?= url('/admin/reports/' . $report['public_id']) ?>"><?= e($report['title']) ?></a><div class="cell-sub"><?= e($report['category']) ?> · <?= e($report['public_id']) ?></div><div class="cell-sub"><?= e($report['format']) ?></div></div></div></td><td><?= e($report['period']) ?></td><td><span class="badge <?= e(status_class($report['status'])) ?>"><?= e($report['status']) ?></span></td><td><?= number_format((int) $report['records']) ?></td><td><div class="cell-title"><?= e(fmt_date($report['generated_at'])) ?></div><div class="cell-sub"><?= e($report['generated_by']) ?></div></td><td class="u-right"><a class="btn btn--outline btn--sm" href="<?= url('/admin/reports/' . $report['public_id']) ?>">Open</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/reports', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' reports']) ?>
</div>
