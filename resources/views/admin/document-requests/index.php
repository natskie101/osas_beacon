<?php
/** @var list<array<string, mixed>> $requests */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--outline" href="<?= url('/admin/reports') ?>">Open Reports</a></div>
<div class="stats">
    <div class="stat"><div class="stat__label">Submitted</div><div class="stat__value"><?= number_format($counts['submitted'] ?? 0) ?></div><div class="stat__sub">New requests</div></div>
    <div class="stat"><div class="stat__label">Under Review</div><div class="stat__value"><?= number_format($counts['review'] ?? 0) ?></div><div class="stat__sub">Being processed</div></div>
    <div class="stat"><div class="stat__label">Ready for Pick-up</div><div class="stat__value"><?= number_format($counts['ready'] ?? 0) ?></div><div class="stat__sub">Awaiting release</div></div>
    <div class="stat"><div class="stat__label">Released</div><div class="stat__value"><?= number_format($counts['released'] ?? 0) ?></div><div class="stat__sub">Completed requests</div></div>
</div>
<form class="filter-bar filter-bar--2" method="GET" action="<?= url('/admin/document-requests') ?>">
    <div class="field"><label for="search">Search requests</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Student, document or reference"></span></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (App\Models\DocumentRequest::statuses() as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/document-requests') ?>">Reset</a></div>
</form>
<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Request Queue</div><div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> requests</div></div><span class="badge badge--brand"><?= number_format($pagination['total']) ?> requests</span></div>
    <?php if ($requests === []): ?><div class="empty-state"><strong>No document requests match these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Student</th><th>Document</th><th>Status</th><th>Requested</th><th>Delivery</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($requests as $requestRecord): ?>
            <?php $nameParts = preg_split('/\s+/', trim((string) $requestRecord['student_name'])) ?: []; ?>
            <tr><td><div class="identity-cell"><span class="avatar avatar--blue"><?= e(initials($nameParts[0] ?? '', $nameParts[count($nameParts) - 1] ?? '')) ?></span><div><a class="cell-title" href="<?= url('/admin/document-requests/' . $requestRecord['public_id']) ?>"><?= e($requestRecord['student_name']) ?></a><div class="cell-sub"><?= e($requestRecord['student_id']) ?> · <?= e($requestRecord['program']) ?></div><div class="cell-sub"><?= e($requestRecord['student_email']) ?></div></div></div></td><td><div class="cell-title"><?= e($requestRecord['document_type']) ?></div><div class="cell-sub mono"><?= e($requestRecord['public_id']) ?></div></td><td><span class="badge <?= e(status_class($requestRecord['status'])) ?>"><?= e($requestRecord['status']) ?></span></td><td><div class="cell-title"><?= e(fmt_date($requestRecord['requested_at'])) ?></div><div class="cell-sub"><?= e(time_ago($requestRecord['requested_at'])) ?></div></td><td><?= e($requestRecord['delivery_mode']) ?><div class="cell-sub"><?= (int) $requestRecord['copies'] ?> cop<?= (int) $requestRecord['copies'] === 1 ? 'y' : 'ies' ?></div></td><td class="u-right"><a class="btn btn--outline btn--sm" href="<?= url('/admin/document-requests/' . $requestRecord['public_id']) ?>">Process</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/document-requests', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' requests']) ?>
</div>
