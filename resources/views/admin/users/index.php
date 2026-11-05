<?php
/** @var list<array<string, mixed>> $accounts */
/** @var array<string, mixed> $pagination */
/** @var array<string, mixed> $filters */
/** @var array<string, mixed> $counts */
/** @var bool $canCreate */
/** @var array<string, mixed> $user */
/** @var string $tab */
$roleBadge = static fn (string $role): string => match ($role) {
    'Coordinator' => 'badge--brand',
    'Administrator' => 'badge--scheduled',
    default => 'badge--info',
};
$joined = !empty($counts['latest_created_at']) ? time_ago((string) $counts['latest_created_at']) : 'recently';
$queueUrl = url('/admin/users?tab=queue');
$directoryUrl = url('/admin/users?tab=directory');
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">User Account &amp; Access Management</h2>
        <p class="page-head__sub">Verify pending registrations and manage portal access and system roles.</p>
    </div>
    <div class="page-head__actions">
        <?php if ($canCreate): ?>
            <a href="<?= url('/admin/users/create') ?>" class="btn btn--primary"><?= App\Support\Icons::render('plus', 14) ?> Add New User</a>
        <?php endif; ?>
    </div>
</div>

<div class="stats um-stats">
    <div class="stat">
        <div class="stat__label">Registered Students</div>
        <div class="stat__value"><?= number_format($counts['students'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--info">All students</span> Joined <?= e($joined) ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">Active Student Accounts</div>
        <div class="stat__value"><?= number_format($counts['active_students'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--verified">Active</span> <?= number_format($counts['signed_in_students'] ?? 0) ?> signed in at least once</div>
    </div>
    <div class="stat">
        <div class="stat__label">Pending Activations</div>
        <div class="stat__value"><?= number_format($counts['pending'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--pending">Awaiting</span> <?= number_format($counts['pending'] ?? 0) ?> tracked in the verification queue</div>
    </div>
    <div class="stat">
        <div class="stat__label">Administrative Staff</div>
        <div class="stat__value"><?= number_format($counts['staff'] ?? 0) ?></div>
        <div class="stat__sub"><?= number_format($counts['coordinators'] ?? 0) ?> coordinator &middot; <?= number_format($counts['administrators'] ?? 0) ?> administrator in the directory</div>
    </div>
</div>

<div class="toolbar um-toolbar">
    <div class="tabs">
        <a class="tab<?= $tab === 'queue' ? ' tab--on' : '' ?>" href="<?= $queueUrl ?>">Verification Queue (<?= number_format($counts['pending'] ?? 0) ?>)</a>
        <a class="tab<?= $tab === 'directory' ? ' tab--on' : '' ?>" href="<?= $directoryUrl ?>">User Directory</a>
    </div>
    <span class="toolbar__note"><?= number_format($counts['pending'] ?? 0) ?> requests awaiting verification</span>
</div>

<form class="filter-bar um-filters" method="GET" action="<?= url('/admin/users') ?>">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div class="field">
        <label for="search">Search the access directory</label>
        <span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" type="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search by name, email, student ID or department..."></span>
    </div>
    <div class="field">
        <label for="role">Role</label>
        <select class="input" id="role" name="role">
            <option value="">All roles</option>
            <?php foreach (config('people.roles', []) as $roleOption): ?>
                <option value="<?= e($roleOption) ?>"<?= ($filters['role'] ?? '') === $roleOption ? ' selected' : '' ?>><?= e($roleOption) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($tab === 'directory'): ?>
        <div class="field">
            <label for="status">Account status</label>
            <select class="input" id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach (['Active', 'Inactive'] as $statusOption): ?>
                    <option value="<?= e($statusOption) ?>"<?= ($filters['status'] ?? '') === $statusOption ? ' selected' : '' ?>><?= e($statusOption) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
</form>

<div class="table-wrap um-table">
    <div class="table-toolbar">
        <div>
            <div class="panel-title">System Access Directory</div>
            <div class="cell-sub"><?= $tab === 'queue' ? 'Pending accounts awaiting verification' : 'Active and inactive portal accounts' ?></div>
        </div>
        <span class="badge badge--brand"><?= number_format($pagination['total']) ?> records</span>
    </div>

    <?php if ($accounts === []): ?>
        <div class="empty-state"><strong>No accounts match the current filters.</strong></div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data">
                <thead>
                    <tr><th>User &amp; Role</th><th>UC Student ID</th><th>UC Email Address</th><th>Department &amp; Program</th><th>Account Status</th><th class="u-right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                        <?php
                        $tone = match ($account['role']) { 'Coordinator' => 'amber', 'Administrator' => 'blue', default => 'green' };
                        $canManage = \App\Policies\UserPolicy::allows('manage', $user, $account);
                        ?>
                        <tr>
                            <td>
                                <div class="identity-cell">
                                    <span class="avatar avatar--<?= $tone ?>"><?= e(initials($account['first_name'], $account['last_name'])) ?></span>
                                    <div>
                                        <a class="cell-title" href="<?= url('/admin/users/' . $account['public_id']) ?>"><?= e(trim($account['first_name'] . ' ' . $account['last_name'])) ?></a>
                                        <span class="badge <?= e($roleBadge($account['role'])) ?>"><?= e(strtoupper($account['role'])) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title"><?= e($account['student_id'] ?: '—') ?></div>
                                <div class="cell-sub"><?= e($account['public_id']) ?></div>
                            </td>
                            <td>
                                <div class="cell-title"><?= e($account['email']) ?></div>
                                <div class="cell-sub"><?= e($account['phone'] ?: 'No phone number') ?></div>
                            </td>
                            <td>
                                <div class="cell-title"><?= e($account['department'] ?: 'No department assigned') ?></div>
                                <div class="cell-sub"><?= e($account['program'] ?: 'No program assigned') ?></div>
                            </td>
                            <td><span class="badge <?= e(status_class($account['status'])) ?>"><?= e($account['status']) ?></span></td>
                            <td class="u-right">
                                <div class="cell-actions">
                                    <?php if ($tab === 'queue'): ?>
                                        <a class="btn btn--primary btn--sm" href="<?= url('/admin/users/' . $account['public_id']) ?>">Review</a>
                                    <?php else: ?>
                                        <?php if ($canManage): ?><a class="btn btn--outline btn--sm" href="<?= url('/admin/users/' . $account['public_id'] . '/edit') ?>">Manage</a><?php endif; ?>
                                        <?php if ($account['status'] === 'Active' && $canManage): ?>
                                            <form method="POST" action="<?= url('/admin/users/' . $account['public_id'] . '/deactivate') ?>" data-confirm="Deactivate this account?"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Deactivate</button></form>
                                        <?php elseif ($account['status'] === 'Inactive' && $canManage): ?>
                                            <form method="POST" action="<?= url('/admin/users/' . $account['public_id'] . '/activate') ?>" data-confirm="Reactivate this account?"><?= csrf_field() ?><button class="btn btn--outline btn--sm" type="submit">Activate</button></form>
                                        <?php endif; ?>
                                        <a class="btn btn--ghost btn--sm" href="<?= url('/admin/users/' . $account['public_id']) ?>">Activity</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?= App\Support\View::render('partials/pagination', [
        'pagination' => $pagination,
        'baseUrl' => '/admin/users',
        'query' => $filters,
        'label' => $tab === 'queue'
            ? 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' pending registration requests'
            : 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' accounts',
    ]) ?>
</div>
