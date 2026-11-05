<?php
/** @var list<array<string, mixed>> $notifications */
/** @var int $unread */
?><div class="page-head">
    <div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div>
    <div class="page-head__actions">
        <?php if (in_array($role, ['Coordinator', 'Administrator'], true)): ?>
            <a class="btn btn--outline" href="<?= url('/admin/activity') ?>">Open Audit Log</a>
        <?php endif; ?>
        <?php if ($unread > 0): ?>
            <form method="POST" action="<?= url('/notifications/read-all') ?>"><?= csrf_field() ?><button class="btn btn--outline" type="submit">Mark All as Read</button></form>
        <?php endif; ?>
    </div>
</div>

<div class="card panel-card">
    <div class="panel-card__head"><div><div class="panel-title">Your Notifications</div><p class="panel-card__sub"><?= number_format($unread) ?> unread of <?= number_format(count($notifications)) ?> recent notifications.</p></div><span class="badge <?= $unread > 0 ? 'badge--pending' : 'badge--published' ?>"><?= $unread > 0 ? $unread . ' unread' : 'All read' ?></span></div>
    <?php if ($notifications === []): ?>
        <div class="empty-state"><strong>You have no notifications yet.</strong><div>Portal updates, approvals and replies will appear here.</div></div>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <?php
                $link = '';
                if (($notification['entity_type'] ?? '') === 'announcement' && !empty($notification['entity_id'])) {
                    $link = url(($role === 'Student' ? '/student/announcements/' : '/admin/announcements/') . $notification['entity_id']);
                } elseif (($notification['entity_type'] ?? '') === 'support' && !empty($notification['entity_id'])) {
                    $link = url(($role === 'Student' ? '/student/support/' : '/admin/support/') . $notification['entity_id']);
                } elseif (($notification['entity_type'] ?? '') === 'document_request' && !empty($notification['entity_id'])) {
                    $link = url(($role === 'Student' ? '/student/document-requests/' : '/admin/document-requests/') . $notification['entity_id']);
                } elseif (($notification['entity_type'] ?? '') === 'lost_found' && !empty($notification['entity_id'])) {
                    $link = url(($role === 'Student' ? '/student/lost-found/' : '/admin/lost-found/') . $notification['entity_id']);
                } elseif (($notification['entity_type'] ?? '') === 'orientation' && !empty($notification['entity_id'])) {
                    $link = url(($role === 'Student' ? '/student/orientation/' : '/admin/orientation/') . $notification['entity_id']);
                } elseif (($notification['entity_type'] ?? '') === 'broadcast' && !empty($notification['entity_id']) && $role !== 'Student') {
                    $link = url('/admin/broadcasts/' . $notification['entity_id']);
                }
                ?>
                <article class="notification-item<?= empty($notification['read_at']) ? ' notification-item--unread' : '' ?>">
                    <span class="notification-item__ico"><?= App\Support\Icons::render($notification['type'] === 'warning' ? 'alert' : ($notification['type'] === 'success' ? 'checkCircle' : 'bell'), 15) ?></span>
                    <div class="notification-item__body">
                        <div class="notification-item__head"><strong><?= e($notification['title']) ?></strong><time><?= e(time_ago($notification['created_at'])) ?></time></div>
                        <p><?= e($notification['message']) ?></p>
                        <div class="notification-item__actions">
                            <?php if ($link !== ''): ?><a class="link" href="<?= e($link) ?>">Open record</a><?php endif; ?>
                            <?php if (empty($notification['read_at'])): ?>
                                <form method="POST" action="<?= url('/notifications/' . $notification['id'] . '/read') ?>"><?= csrf_field() ?><button class="link" type="submit">Mark as read</button></form>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
