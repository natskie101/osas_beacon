<?php
/** @var list<array<string, mixed>> $accounts */
/** @var array<string, mixed> $pagination */
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

$roleBadge = static fn (string $role): string => match ($role) {
    'Coordinator' => 'badge--brand',
    'Administrator' => 'badge--scheduled',
    default => 'badge--info',
};
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
        <?php endif; ?>
    </div>
</div>

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
        </div>
        <span class="badge badge--brand"><?= number_format($pagination['total']) ?> records</span>
    </div>

    <?php if ($accounts === []): ?>
        <div class="empty-state">
            <strong>No accounts match the current filters.</strong>
            <div>Try a different search term, role or status.</div>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data">
                <thead>
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
                        <tr>
                            <td>
                                <div class="identity-cell">
                                    <span class="avatar avatar--<?= $tone ?>"><?= e(initials($account['first_name'], $account['last_name'])) ?></span>
                                    <div>
                                        <a class="cell-title" href="<?= url('/admin/users/' . $account['public_id']) ?>"><?= e(trim($account['first_name'] . ' ' . $account['last_name'])) ?></a>
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
        'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' accounts',
    ]) ?>
</div>
