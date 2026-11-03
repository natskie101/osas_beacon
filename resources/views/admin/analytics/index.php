<?php
/** @var list<array<string, mixed>> $rows */
/** @var array{total_views:int,unique_visitors:int,avg_engagement:float,items:int} $totals */
/** @var list<string> $types */
/** @var array<string, string> $filters */
/** @var array<string, int> $audience */
?><div class="page-head">
    <div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div>
    <a class="btn btn--outline" href="<?= url('/admin/reports') ?>">Open Reports</a>
</div>

<div class="stats">
    <div class="stat"><div class="stat__label">Total Views</div><div class="stat__value"><?= number_format($totals['total_views']) ?></div><div class="stat__sub"></div></div>
    <div class="stat"><div class="stat__label">Unique Visitors</div><div class="stat__value"><?= number_format($totals['unique_visitors']) ?></div><div class="stat__sub"></div></div>
    <div class="stat"><div class="stat__label">Avg Engagement</div><div class="stat__value"><?= number_format($totals['avg_engagement'], 1) ?>%</div><div class="stat__sub"></div></div>
    <div class="stat"><div class="stat__label">Tracked Items</div><div class="stat__value"><?= number_format($totals['items']) ?></div><div class="stat__sub"><?= number_format($audience['active'] ?? 0) ?></div></div>
</div>

<div class="dash-grid">
    <section class="card panel-card">
        <div class="panel-card__head"><div><div class="panel-title">Audience Snapshot</div><p class="panel-card__sub">Active portal accounts available to receive content.</p></div></div>
        <div class="audience-bars">
            <div class="audience-bar"><span>Students</span><strong><?= number_format($audience['students'] ?? 0) ?></strong><div class="progress-bar"><i style="width: <?= min(100, ((int) ($audience['students'] ?? 0) / max(1, (int) ($audience['accounts'] ?? 1))) * 100) ?>%"></i></div></div>
            <div class="audience-bar"><span>Active</span><strong><?= number_format($audience['active'] ?? 0) ?></strong><div class="progress-bar"><i style="width: <?= min(100, ((int) ($audience['active'] ?? 0) / max(1, (int) ($audience['accounts'] ?? 1))) * 100) ?>%"></i></div></div>
            <div class="audience-bar"><span>Pending verification</span><strong><?= number_format($audience['pending'] ?? 0) ?></strong><div class="progress-bar"><i style="width: <?= min(100, ((int) ($audience['pending'] ?? 0) / max(1, (int) ($audience['accounts'] ?? 1))) * 100) ?>%"></i></div></div>
        </div>
    </section>
    <section class="card panel-card">
        <div class="panel-card__head"><div><div class="panel-title">Most Viewed Content</div></div></div>
        <div class="mini-list">
            <?php foreach (array_slice($rows, 0, 5) as $row): ?>
                <div class="mini-item"><span class="log-item__ico"><?= App\Support\Icons::render('activity', 13) ?></span><div><strong><?= e($row['title']) ?></strong><span><?= e($row['type']) ?> · <?= number_format((int) $row['views']) ?> views</span></div><em><?= number_format((float) $row['engagement'], 0) ?>%</em></div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<form class="filter-bar filter-bar--3" method="GET" action="<?= url('/admin/analytics') ?>">
    <div class="field"><label for="search">Search content</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Title, type or reference"></span></div>
    <div class="field"><label for="type">Content type</label><select class="input" id="type" name="type"><option value="">All content types</option><?php foreach ($types as $type): ?><option value="<?= e($type) ?>"<?= ($filters['type'] ?? '') === $type ? ' selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/analytics') ?>">Reset</a></div>
</form>

<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Content Engagement</div><div class="cell-sub"><?= number_format(count($rows)) ?> tracked items match the current filters.</div></div><span class="badge badge--brand">Live data</span></div>
    <?php if ($rows === []): ?><div class="empty-state"><strong>No tracked content matches these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Content</th><th>Type</th><th>Views</th><th>Unique</th><th>Avg Time</th><th>Engagement</th><th>Last Viewed</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?>
            <tr><td><div class="cell-title"><?= e($row['title']) ?></div><div class="cell-sub mono"><?= e($row['public_id']) ?></div></td><td><span class="badge badge--info"><?= e($row['type']) ?></span></td><td><?= number_format((int) $row['views']) ?></td><td><?= number_format((int) $row['unique_visitors']) ?></td><td><?= e($row['avg_time']) ?></td><td><div class="cell-title"><?= number_format((float) $row['engagement'], 1) ?>%</div><div class="progress-bar"><i style="width: <?= min(100, (float) $row['engagement']) ?>%"></i></div></td><td><div class="cell-title"><?= e(fmt_date($row['last_viewed'])) ?></div><div class="cell-sub"><?= e(time_ago($row['last_viewed'])) ?></div></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
</div>
