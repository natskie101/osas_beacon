<?php
/** @var list<array<string, mixed>> $broadcasts */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
?><div class="page-head">
    <div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div>
    <a class="btn btn--primary" href="<?= url('/admin/broadcasts/create') ?>"><?= App\Support\Icons::render('plus', 14) ?> Compose Broadcast</a>
</div>

<div class="stats">
    <div class="stat"><div class="stat__label">Sent</div><div class="stat__value"><?= number_format($counts['sent'] ?? 0) ?></div><div class="stat__sub">Delivered broadcasts</div></div>
    <div class="stat"><div class="stat__label">Scheduled</div><div class="stat__value"><?= number_format($counts['scheduled'] ?? 0) ?></div><div class="stat__sub">Queued for later</div></div>
    <div class="stat"><div class="stat__label">Total Reach</div><div class="stat__value"><?= number_format($counts['reach'] ?? 0) ?></div><div class="stat__sub">Recipients reached</div></div>
    <div class="stat"><div class="stat__label">Opened</div><div class="stat__value"><?= number_format($counts['opened'] ?? 0) ?></div><div class="stat__sub">Portal notification opens</div></div>
</div>

<form class="filter-bar filter-bar--2" method="GET" action="<?= url('/admin/broadcasts') ?>">
    <div class="field"><label for="search">Search broadcasts</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Subject, audience or message"></span></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (['Sent', 'Scheduled', 'Pending', 'Draft'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
<<<<<<< HEAD
    
=======
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/broadcasts') ?>">Reset</a></div>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
</form>

<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Broadcast History</div><div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> broadcasts</div></div><span class="badge badge--brand"><?= number_format($pagination['total']) ?> broadcasts</span></div>
    <?php if ($broadcasts === []): ?><div class="empty-state"><strong>No broadcasts match these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Broadcast</th><th>Channel</th><th>Audience</th><th>Status</th><th>Reach</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($broadcasts as $broadcast): ?>
            <tr>
                <td><div class="identity-cell"><span class="avatar avatar--blue"><?= App\Support\Icons::render('broadcast', 15) ?></span><div><a class="cell-title" href="<?= url('/admin/broadcasts/' . $broadcast['public_id']) ?>"><?= e($broadcast['subject']) ?></a><div class="cell-sub"><?= e($broadcast['public_id']) ?> · <?= e(fmt_date($broadcast['sent_at'] ?: $broadcast['scheduled_at'] ?: $broadcast['created_at'])) ?></div></div></div></td>
                <td><?= e($broadcast['channel']) ?></td>
                <td><?= e($broadcast['audience']) ?></td>
                <td><span class="badge <?= e(status_class($broadcast['status'])) ?>"><?= e($broadcast['status']) ?></span></td>
                <td><div class="cell-title"><?= number_format((int) $broadcast['delivered']) ?></div><div class="cell-sub"><?= number_format((int) $broadcast['reach']) ?> recipients</div></td>
                <td class="u-right"><div class="cell-actions"><a class="btn btn--ghost btn--sm" href="<?= url('/admin/broadcasts/' . $broadcast['public_id']) ?>">Open</a><?php if ($broadcast['status'] !== 'Sent'): ?><a class="btn btn--outline btn--sm" href="<?= url('/admin/broadcasts/' . $broadcast['public_id'] . '/edit') ?>">Edit</a><?php endif; ?></div></td>
            </tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/broadcasts', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' broadcasts']) ?>
</div>
