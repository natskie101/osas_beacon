<?php
/** @var list<array<string, mixed>> $tickets */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
/** @var list<string> $categories */
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div></div>
<div class="stats">
    <div class="stat"><div class="stat__label">Open Tickets</div><div class="stat__value"><?= number_format($counts['open'] ?? 0) ?></div><div class="stat__sub">New concerns</div></div>
    <div class="stat"><div class="stat__label">In Progress</div><div class="stat__value"><?= number_format($counts['in_progress'] ?? 0) ?></div><div class="stat__sub">Being handled</div></div>
    <div class="stat"><div class="stat__label">Resolved</div><div class="stat__value"><?= number_format($counts['resolved'] ?? 0) ?></div><div class="stat__sub">Answered concerns</div></div>
    <div class="stat"><div class="stat__label">Average Rating</div><div class="stat__value"><?= number_format((float) ($counts['avg_rating'] ?? 0), 1) ?>/5</div><div class="stat__sub">Student satisfaction</div></div>
</div>
<form class="filter-bar filter-bar--3" method="GET" action="<?= url('/admin/support') ?>">
    <div class="field"><label for="search">Search tickets</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Student, subject or text"></span></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (['Open', 'In Progress', 'Resolved', 'Closed'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="category">Category</label><select class="input" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($filters['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/support') ?>">Reset</a></div>
</form>
<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Support Queue</div><div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> tickets</div></div><span class="badge badge--brand"><?= number_format($pagination['total']) ?> tickets</span></div>
    <?php if ($tickets === []): ?><div class="empty-state"><strong>No support tickets match these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Student</th><th>Subject</th><th>Status</th><th>Priority</th><th>Submitted</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($tickets as $ticket): ?>
            <tr><td><div class="identity-cell"><span class="avatar avatar--blue"><?= e(initials($ticket['student_name'], '')) ?></span><div><a class="cell-title" href="<?= url('/admin/support/' . $ticket['public_id']) ?>"><?= e($ticket['student_name']) ?></a><div class="cell-sub"><?= e($ticket['student_id']) ?> · <?= e($ticket['program']) ?></div><div class="cell-sub"><?= e($ticket['student_email']) ?></div></div></div></td><td><div class="cell-title"><?= e($ticket['subject']) ?></div><div class="cell-sub"><?= e($ticket['category']) ?> · <?= e($ticket['public_id']) ?></div></td><td><span class="badge <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span></td><td><span class="badge <?= e(status_class($ticket['priority'])) ?>"><?= e($ticket['priority']) ?></span></td><td><div class="cell-title"><?= e(fmt_date($ticket['submitted_at'])) ?></div><div class="cell-sub"><?= e(time_ago($ticket['submitted_at'])) ?></div></td><td class="u-right"><a class="btn btn--outline btn--sm" href="<?= url('/admin/support/' . $ticket['public_id']) ?>">Respond</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/support', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' tickets']) ?>
</div>
