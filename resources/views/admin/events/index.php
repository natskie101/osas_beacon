<?php
/** @var list<array<string, mixed>> $events */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
/** @var list<string> $categories */
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--primary" href="<?= url('/admin/events/create') ?>"><?= App\Support\Icons::render('plus', 14) ?> Create Event</a></div>
<div class="stats">
    <div class="stat"><div class="stat__label">Scheduled</div><div class="stat__value"><?= number_format($counts['scheduled'] ?? 0) ?></div><div class="stat__sub">Upcoming activities</div></div>
    <div class="stat"><div class="stat__label">Completed</div><div class="stat__value"><?= number_format($counts['completed'] ?? 0) ?></div><div class="stat__sub">Finished events</div></div>
    <div class="stat"><div class="stat__label">Registered</div><div class="stat__value"><?= number_format($counts['registered'] ?? 0) ?></div><div class="stat__sub">Across all events</div></div>
    <div class="stat"><div class="stat__label">Cancelled</div><div class="stat__value"><?= number_format($counts['cancelled'] ?? 0) ?></div><div class="stat__sub">Withdrawn activities</div></div>
</div>
<form class="filter-bar filter-bar--3" method="GET" action="<?= url('/admin/events') ?>">
    <div class="field"><label for="search">Search events</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Title, venue or organizer"></span></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (['Scheduled', 'Ongoing', 'Completed', 'Cancelled'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="category">Category</label><select class="input" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($filters['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div>
<<<<<<< HEAD
    
=======
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/events') ?>">Reset</a></div>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
</form>
<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Event Calendar</div><div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> events</div></div><span class="badge badge--brand"><?= number_format($pagination['total']) ?> events</span></div>
    <?php if ($events === []): ?><div class="empty-state"><strong>No events match these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Event</th><th>Schedule</th><th>Venue</th><th>Status</th><th>Registered</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($events as $event): ?>
            <tr>
                <td><div class="identity-cell"><span class="avatar avatar--green"><?= App\Support\Icons::render('calendar', 15) ?></span><div><a class="cell-title" href="<?= url('/admin/events/' . $event['public_id']) ?>"><?= e($event['title']) ?></a><div class="cell-sub"><?= e($event['category']) ?> · <?= e($event['public_id']) ?></div><div class="cell-sub"><?= e($event['organizer']) ?></div></div></div></td>
                <td><div class="cell-title"><?= e(fmt_date($event['start_at'])) ?></div><div class="cell-sub"><?= e(date('g:i A', strtotime((string) $event['start_at']))) ?><?= $event['end_at'] ? ' – ' . e(date('g:i A', strtotime((string) $event['end_at']))) : '' ?></div></td>
                <td><div class="cell-title"><?= e($event['venue']) ?></div><div class="cell-sub"><?= e($event['mode']) ?></div></td>
                <td><span class="badge <?= e(status_class($event['status'])) ?>"><?= e($event['status']) ?></span></td>
                <td><?= number_format((int) $event['registered']) ?> / <?= number_format((int) $event['capacity']) ?></td>
                <td class="u-right"><div class="cell-actions"><a class="btn btn--ghost btn--sm" href="<?= url('/admin/events/' . $event['public_id']) ?>">Open</a><a class="btn btn--outline btn--sm" href="<?= url('/admin/events/' . $event['public_id'] . '/edit') ?>">Edit</a></div></td>
            </tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/events', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' events']) ?>
</div>
