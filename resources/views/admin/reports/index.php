<?php
<<<<<<< HEAD
/** @var list<array<string, mixed>> $modules */
/** @var array{rate:float,mandatory:int,students:int,compliant:int,in_progress:int,not_started:int,reports:int,records:int} $summary */
/** @var array<string, string> $filters */
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">Reports</h2>
        <p class="page-head__sub">Generate and export official reports for the Student Orientation &amp; Information Module.</p>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <div class="stat__label">Cohort Orientation Rate</div>
        <div class="stat__value"><?= number_format($summary['rate'], 0) ?>%</div>
        <div class="stat__sub"><?= number_format($summary['mandatory']) ?> mandatory modules &times; <?= number_format($summary['students']) ?> students</div>
    </div>
    <div class="stat">
        <div class="stat__label">Mandatory Modules</div>
        <div class="stat__value"><?= number_format($summary['mandatory']) ?></div>
        <div class="stat__sub">required for freshmen clearance</div>
    </div>
    <div class="stat">
        <div class="stat__label">Fully Compliant</div>
        <div class="stat__value"><?= number_format($summary['compliant']) ?> / <?= number_format($summary['students']) ?></div>
        <div class="stat__sub"><span class="badge badge--verified">Cleared</span> <?= number_format($summary['in_progress']) ?> in progress &middot; <?= number_format($summary['not_started']) ?> not started</div>
    </div>
    <div class="stat">
        <div class="stat__label">Orientation Reports on File</div>
        <div class="stat__value"><?= number_format($summary['reports']) ?></div>
        <div class="stat__sub"><?= number_format($summary['records']) ?> records compiled</div>
    </div>
</div>

<form class="filter-bar report-filters" method="GET" action="<?= url('/admin/reports') ?>">
    <div class="field">
        <label for="search">Search reports</label>
        <span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search by report title or reference..."></span>
    </div>
    <div class="field">
        <label for="period">Reporting period</label>
        <select class="input" id="period" name="period">
            <option value="">All time</option>
            <option value="30"<?= ($filters['period'] ?? '') === '30' ? ' selected' : '' ?>>Last 30 days</option>
            <option value="90"<?= ($filters['period'] ?? '') === '90' ? ' selected' : '' ?>>Last 90 days</option>
        </select>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select class="input" id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (['On Track', 'Needs Push', 'Critical'] as $status): ?>
                <option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="panel-title report-section-title">1. Mandatory Orientation Module Progress</div>

<div class="table-wrap">
    <?php if ($modules === []): ?>
        <div class="empty-state"><strong>No orientation modules match the current filters.</strong></div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data report-table">
                <thead>
                    <tr><th>Mandatory Module</th><th>Students Completed</th><th>Portal Learners</th><th>Est. Duration</th><th>Last Updated</th><th>Compliance</th><th class="u-right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $module): ?>
                        <tr>
                            <td>
                                <div class="cell-title"><?= e($module['title']) ?></div>
                                <div class="cell-sub mono"><?= e($module['public_id']) ?> &middot; <?= e($module['version']) ?> &middot; <?= e($module['category']) ?></div>
                                <div class="cell-sub"><?= $module['quiz_status'] !== null ? 'Quiz ' . e($module['quiz_status']) . ' &middot; ' . number_format((float) $module['quiz_pass_rate'], 0) . '% pass rate &middot; ' . number_format((float) $module['quiz_average'], 0) . '% average' : 'No assessment' ?></div>
                            </td>
                            <td>
                                <div class="cell-title"><?= number_format((int) $module['completed']) ?> / <?= number_format((int) $module['total_students']) ?> tracked students</div>
                                <div class="progress-bar"><span style="width: <?= min(100, (float) $module['percent']) ?>%"></span></div>
                                <div class="cell-sub"><?= number_format((float) $module['percent'], 0) ?>% completion</div>
                            </td>
                            <td><?= number_format((int) $module['learners']) ?></td>
                            <td><?= number_format((int) $module['duration_minutes']) ?> mins</td>
                            <td><?= e(fmt_date($module['updated_at'])) ?></td>
                            <td><span class="badge <?= e($module['compliance']['tone']) ?>"><?= e(strtoupper($module['compliance']['label'])) ?></span></td>
                            <td class="u-right"><a class="btn btn--primary btn--sm" href="<?= url('/admin/reports/orientation/' . $module['public_id']) ?>"><?= App\Support\Icons::render('eye', 13) ?> View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
=======
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
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
</div>
