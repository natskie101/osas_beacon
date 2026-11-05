<?php
/** @var array<string, mixed> $target */
/** @var array<string, mixed>|null $schoolFile */
/** @var list<array<string, mixed>> $activity */
/** @var list<array<string, mixed>> $notifications */
/** @var bool $canManage */
/** @var bool $canVerify */
/** @var bool $canReset */
/** @var bool $canUpload */
/** @var array<string, mixed> $user */
$fullName = trim(($target['first_name'] ?? '') . ' ' . ($target['last_name'] ?? ''));
$isSelf = (int) $user['id'] === (int) $target['id'];
$tone = match ($target['role']) { 'Coordinator' => 'amber', 'Administrator' => 'blue', default => 'green' };
$roleBadge = match ($target['role']) { 'Coordinator' => 'badge--brand', 'Administrator' => 'badge--scheduled', default => 'badge--info' };
$fileSize = '';

if ($schoolFile !== null) {
    $bytes = (int) $schoolFile['size_bytes'];
    $fileSize = $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format(max(1, $bytes / 1024), 0) . ' KB';
}
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/admin/users') ?>">User Management</a> / <?= e($target['public_id']) ?></div>
        <h2 class="page-head__title"><?= e($fullName) ?></h2>
        <p class="page-head__sub"><?= e($target['public_id']) ?> · <?= e($target['role']) ?> account · <?= e($target['email']) ?></p>
    </div>
    <div class="page-head__actions">
        <a href="<?= url('/admin/users') ?>" class="btn btn--outline">Back to Directory</a>
        <?php if ($canManage): ?>
            <a href="<?= url('/admin/users/' . $target['public_id'] . '/edit') ?>" class="btn btn--primary">
                <?= App\Support\Icons::render('edit', 14) ?> Edit Account
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="profile-hero">
    <div class="profile-hero__identity">
        <span class="avatar avatar--lg avatar--<?= $tone ?>"><?= e(initials($target['first_name'], $target['last_name'])) ?></span>
        <div>
            <div class="profile-hero__name"><?= e($fullName) ?></div>
            <div class="profile-hero__meta">
                <span class="badge <?= $roleBadge ?>"><?= e($target['role']) ?></span>
                <span class="badge <?= e(status_class($target['status'])) ?>"><?= e($target['status']) ?></span>
                <?php if ($target['email_verified_at']): ?>
                    <span class="badge badge--verified">Email verified</span>
                <?php endif; ?>
                <?php if ($isSelf): ?><span class="badge badge--draft">Your account</span><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="profile-hero__facts">
        <div><span>Last sign-in</span><strong><?= e($target['last_login_at'] ? time_ago($target['last_login_at']) : 'Never') ?></strong></div>
        <div><span>Created</span><strong><?= e(fmt_date($target['created_at'])) ?></strong></div>
        <div><span>Account tier</span><strong><?= e($target['account_tier']) ?></strong></div>
    </div>
</div>

<?php if ($target['status'] === 'Pending'): ?>
    <div class="alert alert--warning" role="status">
        <span class="alert__ico"><?= App\Support\Icons::render('clock', 18) ?></span>
        <div>
            <div class="alert__title">Verification required</div>
            <div class="alert__text">This account cannot sign in until a coordinator verifies the student ID and contact details.</div>
        </div>
    </div>
<?php endif; ?>

<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card">
            <div class="panel-card__head">
                <div>
                    <div class="panel-title">Profile & Access Details</div>
                    <p class="panel-card__sub">Server-side identity, role and academic information.</p>
                </div>
                <span class="badge badge--brand"><?= e($target['access_level'] ?: $target['role']) ?></span>
            </div>

            <dl class="detail-list">
                <div><dt>Email address</dt><dd><?= e($target['email']) ?></dd></div>
                <div><dt>Username</dt><dd class="mono"><?= e($target['username']) ?></dd></div>
                <div><dt>Middle name</dt><dd><?= e($target['middle_name'] ?: 'Not provided') ?></dd></div>
                <div><dt>Student ID</dt><dd><?= e($target['student_id'] ?: 'Not assigned') ?></dd></div>
                <div><dt>Age</dt><dd><?= e($target['age'] ? (string) $target['age'] : 'Not provided') ?></dd></div>
                <div><dt>Department</dt><dd><?= e($target['department'] ?: 'Not set') ?></dd></div>
                <div><dt>Program</dt><dd><?= e($target['program'] ?: 'Not applicable') ?></dd></div>
                <div><dt>Year level</dt><dd><?= e($target['year_level'] ?: 'Not applicable') ?></dd></div>
                <div><dt>Phone</dt><dd><?= e($target['phone'] ?: 'Not provided') ?></dd></div>
                <div><dt>Requested at</dt><dd><?= e(fmt_datetime($target['requested_at'])) ?></dd></div>
                <div><dt>Last update</dt><dd><?= e(fmt_datetime($target['updated_at'])) ?></dd></div>
                <div><dt>Notifications</dt><dd><?= count($notifications) ?> recent</dd></div>
            </dl>
        </section>

        <section class="card panel-card">
            <div class="panel-card__head">
                <div>
                    <div class="panel-title">Account Activity Timeline</div>
                    <p class="panel-card__sub">Sign-ins, verification decisions and account changes recorded by the server.</p>
                </div>
                <span class="badge badge--brand"><?= count($activity) ?> events</span>
            </div>

            <?php if ($activity === []): ?>
                <div class="empty-state">No activity has been recorded for this account yet.</div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($activity as $entry): ?>
                        <?php
                        $eventTone = match ($entry['tone'] ?? 'brand') {
                            'green', 'success' => 'timeline__dot--green',
                            'amber', 'warning' => 'timeline__dot--amber',
                            'blue', 'info' => 'timeline__dot--blue',
                            'red', 'danger' => 'timeline__dot--red',
                            default => 'timeline__dot--brand',
                        };
                        ?>
                        <div class="timeline__item">
                            <span class="timeline__dot <?= $eventTone ?>"><?= App\Support\Icons::render($entry['icon'] ?? 'info', 12) ?></span>
                            <div class="timeline__body">
                                <div class="timeline__title"><?= e($entry['title']) ?></div>
                                <div class="timeline__text"><?= e($entry['text']) ?></div>
                                <div class="timeline__meta">
                                    <?= e($entry['actor_display'] ?: $entry['actor_name'] ?: 'System') ?>
                                    · <?= e(time_ago($entry['created_at'])) ?>
                                    · <?= e(fmt_datetime($entry['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($notifications !== []): ?>
            <section class="card panel-card">
                <div class="panel-card__head">
                    <div>
                        <div class="panel-title">Recent Notifications Sent</div>
                        <p class="panel-card__sub">In-app messages delivered to this user's portal bell.</p>
                    </div>
                </div>
                <div class="log-list log-list--card">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="log-item">
                            <span class="log-item__ico <?= $notification['read_at'] ? 'log-item__ico--green' : 'log-item__ico--amber' ?>">
                                <?= App\Support\Icons::render($notification['read_at'] ? 'checkCircle' : 'bell', 13) ?>
                            </span>
                            <div class="log-item__body">
                                <div class="log-item__title"><?= e($notification['title']) ?></div>
                                <div class="log-item__text"><?= e($notification['message']) ?></div>
                                <div class="log-item__time"><?= e(time_ago($notification['created_at'])) ?> · <?= $notification['read_at'] ? 'Read' : 'Unread' ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <aside class="detail-grid__side">
        <section class="card panel-card">
            <div class="panel-card__head">
                <div>
                    <div class="panel-title">Verification & Status</div>
                    <p class="panel-card__sub">These actions are enforced by the server and written to the audit log.</p>
                </div>
            </div>

            <div class="action-stack">
                <?php if ($target['status'] === 'Pending' && $canVerify): ?>
                    <form method="POST" action="<?= url('/admin/users/' . $target['public_id'] . '/verify') ?>" data-confirm="Verify this account and allow sign-in?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary btn--block">
                            <?= App\Support\Icons::render('shield', 14) ?> Verify & Activate
                        </button>
                    </form>

                    <details class="inline-details">
                        <summary class="btn btn--outline btn--block">Reject Verification</summary>
                        <form method="POST" action="<?= url('/admin/users/' . $target['public_id'] . '/reject') ?>" class="inline-details__body">
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="reason">Reason (optional)</label>
                                <textarea class="input" id="reason" name="reason" rows="3" maxlength="500" placeholder="Explain what could not be verified."></textarea>
                            </div>
                            <button type="submit" class="btn btn--danger btn--block">Reject Account</button>
                        </form>
                    </details>
                <?php endif; ?>

                <?php if ($target['status'] === 'Active' && $canManage && !$isSelf): ?>
                    <form method="POST" action="<?= url('/admin/users/' . $target['public_id'] . '/deactivate') ?>" data-confirm="Deactivate this account? The user will be signed out and blocked from signing in.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger btn--block">Deactivate Account</button>
                    </form>
                <?php elseif ($target['status'] === 'Inactive' && $canManage): ?>
                    <form method="POST" action="<?= url('/admin/users/' . $target['public_id'] . '/activate') ?>" data-confirm="Reactivate this account?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary btn--block">Reactivate Account</button>
                    </form>
                <?php endif; ?>

                <?php if ($canReset): ?>
                    <form method="POST" action="<?= url('/admin/users/' . $target['public_id'] . '/reset-password') ?>" data-confirm="Generate and send a password reset link to this user?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--outline btn--block">
                            <?= App\Support\Icons::render('mail', 14) ?> Send Password Reset
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($isSelf && $target['status'] === 'Active'): ?>
                    <p class="action-note">Status changes are disabled for your own account to prevent accidental lockout.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card panel-card">
            <div class="panel-card__head">
                <div>
                    <div class="panel-title">School ID Document</div>
                    <p class="panel-card__sub">Stored outside the public document root and streamed only to authorised staff.</p>
                </div>
            </div>

            <?php if ($schoolFile !== null): ?>
                <div class="file-row">
                    <span class="file-row__icon"><?= App\Support\Icons::render('fileText', 18) ?></span>
                    <div class="file-row__body">
                        <div class="cell-title"><?= e($schoolFile['original_name']) ?></div>
                        <div class="cell-sub"><?= e($fileSize) ?> · <?= e(fmt_datetime($schoolFile['created_at'])) ?></div>
                    </div>
                    <a href="<?= url('/admin/users/' . $target['public_id'] . '/school-id') ?>" class="btn btn--ghost btn--sm">
                        <?= App\Support\Icons::render('download', 13) ?> Download
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state empty-state--compact">No school ID document is attached.</div>
            <?php endif; ?>

            <?php if ($canUpload): ?>
                <form method="POST" action="<?= url('/admin/users/' . $target['public_id'] . '/school-id') ?>" enctype="multipart/form-data" class="upload-form">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="school_id"><?= $schoolFile !== null ? 'Replace document' : 'Upload document' ?></label>
                        <input class="input" id="school_id" name="school_id" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                        <div class="help-text">PDF, JPG or PNG. Maximum 5 MB.</div>
                    </div>
                    <button type="submit" class="btn btn--primary btn--block"><?= App\Support\Icons::render('upload', 14) ?> Upload School ID</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="card panel-card">
            <div class="panel-title">Account Reference</div>
            <dl class="mini-facts">
                <div><dt>Public ID</dt><dd class="mono"><?= e($target['public_id']) ?></dd></div>
                <div><dt>Role</dt><dd><?= e($target['role']) ?></dd></div>
                <div><dt>Status</dt><dd><?= e($target['status']) ?></dd></div>
                <div><dt>Tier</dt><dd><?= e($target['account_tier']) ?></dd></div>
            </dl>
        </section>
    </aside>
</div>
