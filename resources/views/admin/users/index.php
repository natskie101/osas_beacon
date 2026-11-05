<?php
/** @var list<array<string, mixed>> $accounts */
/** @var array<string, mixed> $pagination */
<<<<<<< HEAD
/** @var array<string, mixed> $filters */
/** @var array<string, mixed> $counts */
/** @var bool $canCreate */
/** @var array<string, mixed> $user */
/** @var string $tab */
=======
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
/** @var bool $canCreate */

$buildQuery = static function (array $overrides = []) use ($filters): string {
    $query = array_filter(array_merge($filters, $overrides), static fn (mixed $value): bool => $value !== null && $value !== '');
    unset($query['page']);

    return $query === [] ? '' : '?' . http_build_query($query);
};

$activeTab = 'all';

if (($filters['group'] ?? '') === 'students') {
    $activeTab = 'students';
} elseif (($filters['group'] ?? '') === 'staff') {
    $activeTab = 'staff';
} elseif (($filters['status'] ?? '') === 'Pending') {
    $activeTab = 'pending';
} elseif (($filters['status'] ?? '') === 'Active') {
    $activeTab = 'active';
} elseif (($filters['status'] ?? '') === 'Inactive') {
    $activeTab = 'inactive';
}

>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
$roleBadge = static fn (string $role): string => match ($role) {
    'Coordinator' => 'badge--brand',
    'Administrator' => 'badge--scheduled',
    default => 'badge--info',
};
<<<<<<< HEAD
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
=======
?><div class="page-head">
    <div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
    <div class="page-head__actions">
        <?php if ($canCreate): ?>
            <a href="<?= url('/admin/users/create') ?>" class="btn btn--primary">
                <?= App\Support\Icons::render('plus', 14) ?> Add Account
            </a>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        <?php endif; ?>
    </div>
</div>

<<<<<<< HEAD
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
=======
<div class="toolbar">
    <div class="tabs" role="tablist" aria-label="Account filters">
        <a class="tab<?= $activeTab === 'all' ? ' tab--on' : '' ?>" href="<?= url('/admin/users') ?>">All Accounts <span class="tab__count"><?= number_format($counts['total']) ?></span></a>
        <a class="tab<?= $activeTab === 'pending' ? ' tab--on' : '' ?>" href="<?= url('/admin/users') . $buildQuery(['status' => 'Pending', 'group' => '']) ?>">Verification Queue <span class="tab__count"><?= number_format($counts['pending']) ?></span></a>
        <a class="tab<?= $activeTab === 'students' ? ' tab--on' : '' ?>" href="<?= url('/admin/users') . $buildQuery(['group' => 'students', 'status' => '', 'role' => '']) ?>">Students <span class="tab__count"><?= number_format($counts['students']) ?></span></a>
        <a class="tab<?= $activeTab === 'staff' ? ' tab--on' : '' ?>" href="<?= url('/admin/users') . $buildQuery(['group' => 'staff', 'status' => '', 'role' => '']) ?>">Staff <span class="tab__count"><?= number_format($counts['staff']) ?></span></a>
        <a class="tab<?= $activeTab === 'active' ? ' tab--on' : '' ?>" href="<?= url('/admin/users') . $buildQuery(['status' => 'Active', 'group' => '', 'role' => '']) ?>">Active <span class="tab__count"><?= number_format($counts['active']) ?></span></a>
        <a class="tab<?= $activeTab === 'inactive' ? ' tab--on' : '' ?>" href="<?= url('/admin/users') . $buildQuery(['status' => 'Inactive', 'group' => '', 'role' => '']) ?>">Inactive <span class="tab__count"><?= number_format($counts['inactive']) ?></span></a>
    </div>
    <span class="toolbar__note"><?= number_format($counts['pending']) ?> pending verification</span>
</div>

<form class="filter-bar filter-bar--4" method="GET" action="<?= url('/admin/users') ?>">
    <?php if (($filters['group'] ?? '') !== ''): ?>
        <input type="hidden" name="group" value="<?= e($filters['group']) ?>">
    <?php endif; ?>
    <div class="field">
        <label for="search">Search</label>
        <span class="input-group">
            <span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span>
            <input class="input" id="search" name="search" type="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Name, email, student ID or account ID">
        </span>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
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
<<<<<<< HEAD
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
=======
    <div class="field">
        <label for="status">Status</label>
        <select class="input" id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (config('people.statuses', []) as $statusOption): ?>
                <option value="<?= e($statusOption) ?>"<?= ($filters['status'] ?? '') === $statusOption ? ' selected' : '' ?>><?= e($statusOption) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="sort">Sort</label>
        <div class="field field--inline-tight">
            <select class="input" id="sort" name="sort">
                <option value="name"<?= ($filters['sort'] ?? '') === 'name' ? ' selected' : '' ?>>Name</option>
                <option value="created"<?= ($filters['sort'] ?? '') === 'created' ? ' selected' : '' ?>>Date created</option>
                <option value="last_login"<?= ($filters['sort'] ?? '') === 'last_login' ? ' selected' : '' ?>>Last sign-in</option>
                <option value="status"<?= ($filters['sort'] ?? '') === 'status' ? ' selected' : '' ?>>Status</option>
                <option value="role"<?= ($filters['sort'] ?? '') === 'role' ? ' selected' : '' ?>>Role</option>
            </select>
            <select class="input" id="direction" name="direction" aria-label="Sort direction">
                <option value="asc"<?= ($filters['direction'] ?? '') === 'asc' ? ' selected' : '' ?>>A–Z</option>
                <option value="desc"<?= ($filters['direction'] ?? '') === 'desc' ? ' selected' : '' ?>>Z–A</option>
            </select>
        </div>
    </div>
    <div class="filter-bar__actions">
        <button type="submit" class="btn btn--primary">Apply Filters</button>
        <a href="<?= url('/admin/users') ?>" class="btn btn--ghost">Reset</a>
    </div>
</form>

<div class="table-wrap">
    <div class="table-toolbar">
        <div>
            <div class="panel-title">Account Directory</div>
            <div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> accounts</div>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        </div>
        <span class="badge badge--brand"><?= number_format($pagination['total']) ?> records</span>
    </div>

    <?php if ($accounts === []): ?>
<<<<<<< HEAD
        <div class="empty-state"><strong>No accounts match the current filters.</strong></div>
=======
        <div class="empty-state">
            <strong>No accounts match the current filters.</strong>
            <div>Try a different search term, role or status.</div>
        </div>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    <?php else: ?>
        <div class="table-scroll">
            <table class="data">
                <thead>
<<<<<<< HEAD
                    <tr><th>User &amp; Role</th><th>UC Student ID</th><th>UC Email Address</th><th>Department &amp; Program</th><th>Account Status</th><th class="u-right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                        <?php
                        $tone = match ($account['role']) { 'Coordinator' => 'amber', 'Administrator' => 'blue', default => 'green' };
                        $canManage = \App\Policies\UserPolicy::allows('manage', $user, $account);
                        ?>
=======
                    <tr>
                        <th>Account</th>
                        <th>Role</th>
                        <th>Student / Program</th>
                        <th>Status</th>
                        <th>Last sign-in</th>
                        <th class="u-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                        <?php $tone = match ($account['role']) { 'Coordinator' => 'amber', 'Administrator' => 'blue', default => 'green' }; ?>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
                        <tr>
                            <td>
                                <div class="identity-cell">
                                    <span class="avatar avatar--<?= $tone ?>"><?= e(initials($account['first_name'], $account['last_name'])) ?></span>
                                    <div>
                                        <a class="cell-title" href="<?= url('/admin/users/' . $account['public_id']) ?>"><?= e(trim($account['first_name'] . ' ' . $account['last_name'])) ?></a>
<<<<<<< HEAD
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
                            <td><span class="badge <?= e(status_class($account['status'])) ?>"><?= e(strtoupper($account['status'])) ?></span></td>
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
=======
                                        <div class="cell-sub"><?= e($account['email']) ?></div>
                                        <div class="cell-sub mono"><?= e($account['public_id']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge <?= $roleBadge($account['role']) ?>"><?= e($account['role']) ?></span></td>
                            <td>
                                <div class="cell-title"><?= e($account['student_id'] ?: '—') ?></div>
                                <div class="cell-sub"><?= e($account['program'] ?: ($account['department'] ?: 'No program assigned')) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= e(status_class($account['status'])) ?>"><?= e($account['status']) ?></span>
                                <?php if ($account['status'] === 'Pending'): ?>
                                    <div class="cell-sub">Awaiting review</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="cell-title"><?= e($account['last_login_at'] ? time_ago($account['last_login_at']) : 'Never') ?></div>
                                <div class="cell-sub"><?= e($account['last_login_at'] ? fmt_datetime($account['last_login_at']) : 'No sign-in recorded') ?></div>
                            </td>
                            <td class="u-right">
                                <div class="cell-actions">
                                    <?php if ($account['status'] === 'Pending'): ?>
                                        <a class="btn btn--outline btn--sm" href="<?= url('/admin/users/' . $account['public_id']) ?>">Review</a>
                                    <?php endif; ?>
                                    <a class="btn btn--ghost btn--sm" href="<?= url('/admin/users/' . $account['public_id']) ?>">View</a>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
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
<<<<<<< HEAD
        'label' => $tab === 'queue'
            ? 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' pending registration requests'
            : 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' accounts',
=======
        'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' accounts',
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    ]) ?>
</div>
