<?php
/** @var array<string, mixed> $announcement */
/** @var list<array<string, mixed>> $attachments */
/** @var list<array<string, mixed>> $related */
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/student/announcements') ?>">Announcements</a> / <?= e($announcement['category']) ?></div>
        <h2 class="page-head__title"><?= e($announcement['title']) ?></h2>
        <p class="page-head__sub"><?= e($announcement['author']) ?> · <?= e(fmt_datetime($announcement['published_at'])) ?> · <?= e($announcement['audience']) ?></p>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-grid__main">
        <article class="card panel-card content-article">
            <?php if (!empty($announcement['summary'])): ?><p class="content-lede"><?= e($announcement['summary']) ?></p><?php endif; ?>
            <div class="content-detail"><?= body_html($announcement['body']) ?></div>

            <?php if ($attachments !== []): ?>
                <div class="content-attachments">
                    <?php foreach ($attachments as $attachment): ?>
                        <?php if (!empty($attachment['file_id'])): ?>
                            <a class="attachment-chip" href="<?= url('/student/announcements/' . $announcement['public_id'] . '/attachment/' . $attachment['file_id']) ?>"><?= App\Support\Icons::render('download', 14) ?><span><?= e($attachment['name']) ?></span><em><?= e($attachment['size'] ?? '') ?></em></a>
                        <?php else: ?>
                            <span class="attachment-chip"><?= App\Support\Icons::render('fileText', 14) ?><span><?= e($attachment['name']) ?></span><em><?= e($attachment['size'] ?? '') ?></em></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </div>
    <aside class="detail-grid__side">
        <section class="card panel-card">
            <div class="panel-title">Notice Information</div>
            <dl class="detail-list">
                <div><dt>Reference</dt><dd class="mono"><?= e($announcement['public_id']) ?></dd></div>
                <div><dt>Category</dt><dd><?= e($announcement['category']) ?></dd></div>
                <div><dt>Audience</dt><dd><?= e($announcement['audience']) ?></dd></div>
                <div><dt>Published</dt><dd><?= e(fmt_datetime($announcement['published_at'])) ?></dd></div>
                <div><dt>Views</dt><dd><?= number_format((int) $announcement['views'] + 1) ?></dd></div>
            </dl>
        </section>
        <?php if ($related !== []): ?>
            <section class="card panel-card">
                <div class="panel-title">More Announcements</div>
                <div class="mini-list">
                    <?php foreach (array_slice($related, 0, 5) as $item): ?>
                        <a class="mini-item" href="<?= url('/student/announcements/' . $item['public_id']) ?>">
                            <span class="log-item__ico"><?= App\Support\Icons::render('megaphone', 13) ?></span>
                            <div><strong><?= e($item['title']) ?></strong><span><?= e(fmt_date($item['published_at'])) ?></span></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </aside>
</div>
