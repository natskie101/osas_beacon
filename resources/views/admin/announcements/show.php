<?php
/** @var array<string, mixed> $announcement */
/** @var list<array<string, mixed>> $attachments */
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/admin/announcements') ?>">Announcements</a> / <?= e($announcement['public_id']) ?></div>
        <h2 class="page-head__title"><?= e($announcement['title']) ?></h2>
        <p class="page-head__sub"><?= e($announcement['category']) ?> · <?= e($announcement['audience']) ?> · <?= e($announcement['status']) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--outline" href="<?= url('/admin/announcements/' . $announcement['public_id'] . '/edit') ?>"><?= App\Support\Icons::render('edit', 14) ?> Edit</a>
        <?php if ($announcement['status'] !== 'Published'): ?>
            <form method="POST" action="<?= url('/admin/announcements/' . $announcement['public_id'] . '/publish') ?>"><?= csrf_field() ?><button class="btn btn--primary" type="submit"><?= App\Support\Icons::render('send', 14) ?> Publish</button></form>
        <?php endif; ?>
        <?php if ($announcement['status'] !== 'Archived'): ?>
            <form method="POST" action="<?= url('/admin/announcements/' . $announcement['public_id'] . '/archive') ?>"><?= csrf_field() ?><button class="btn btn--ghost" type="submit">Archive</button></form>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card">
            <div class="panel-card__head">
                <div>
                    <div class="panel-title">Announcement Content</div>
                    <p class="panel-card__sub">Rendered exactly as students see it in the portal.</p>
                </div>
                <span class="badge <?= e(status_class($announcement['status'])) ?>"><?= e($announcement['status']) ?></span>
            </div>
            <?php if (!empty($announcement['summary'])): ?>
                <p class="content-lede"><?= e($announcement['summary']) ?></p>
            <?php endif; ?>
            <div class="content-detail"><?= body_html($announcement['body']) ?></div>

            <?php if ($attachments !== []): ?>
                <div class="content-attachments">
                    <?php foreach ($attachments as $attachment): ?>
                        <?php if (!empty($attachment['file_id'])): ?>
                            <a class="attachment-chip" href="<?= url('/admin/announcements/' . $announcement['public_id'] . '/attachment/' . $attachment['file_id']) ?>">
                                <?= App\Support\Icons::render('download', 14) ?><span><?= e($attachment['name']) ?></span><em>Download<?= !empty($attachment['size']) ? ' · ' . e($attachment['size']) : '' ?></em>
                            </a>
                        <?php else: ?>
                            <span class="attachment-chip"><?= App\Support\Icons::render('fileText', 14) ?><span><?= e($attachment['name']) ?></span><em><?= e($attachment['size'] ?? '') ?></em></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card panel-card">
            <div class="panel-card__head"><div><div class="panel-title">Recent Portal Activity</div><p class="panel-card__sub">Audit entries recorded by the server.</p></div><a class="link" href="<?= url('/admin/activity') ?>">Full activity log</a></div>
            <div class="mini-list">
                <?php foreach (array_slice($audit ?? [], 0, 6) as $entry): ?>
                    <div class="mini-item">
                        <span class="log-item__ico"><?= App\Support\Icons::render($entry['icon'] ?? 'info', 13) ?></span>
                        <div><strong><?= e($entry['title']) ?></strong><span><?= e($entry['text']) ?></span></div>
                        <em><?= e(time_ago($entry['created_at'])) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <aside class="detail-grid__side">
        <section class="card panel-card">
            <div class="panel-title">Publishing Details</div>
            <dl class="detail-list">
                <div><dt>Reference</dt><dd class="mono"><?= e($announcement['public_id']) ?></dd></div>
                <div><dt>Category</dt><dd><?= e($announcement['category']) ?></dd></div>
                <div><dt>Audience</dt><dd><?= e($announcement['audience']) ?></dd></div>
                <div><dt>Priority</dt><dd><?= e($announcement['priority']) ?></dd></div>
                <div><dt>Author</dt><dd><?= e($announcement['author']) ?></dd></div>
                <div><dt>Published</dt><dd><?= e(fmt_datetime($announcement['published_at'])) ?></dd></div>
                <div><dt>Scheduled</dt><dd><?= e(fmt_datetime($announcement['scheduled_for'])) ?></dd></div>
                <div><dt>Last updated</dt><dd><?= e(fmt_datetime($announcement['updated_at'])) ?></dd></div>
                <div><dt>Views</dt><dd><?= number_format((int) $announcement['views']) ?></dd></div>
            </dl>
        </section>
        <section class="card panel-card">
            <div class="panel-title">Visibility</div>
            <p class="panel-card__text"><?= $announcement['status'] === 'Published' ? 'This notice is live in the student portal and appears in the recipient notification bell.' : 'This notice is not currently visible to students.' ?></p>
            <a class="btn btn--outline btn--block" href="<?= url('/admin/announcements/' . $announcement['public_id'] . '/edit') ?>">Change Publishing Settings</a>
        </section>
    </aside>
</div>
