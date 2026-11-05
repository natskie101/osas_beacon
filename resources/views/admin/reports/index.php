<?php
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
</div>
