<?php
/** @var array<string, mixed> $item */
/** @var list<array<string, mixed>> $claims */
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="<?= url('/admin/lost-found') ?>">Lost & Found</a> / <?= e($item['public_id']) ?></div><h2 class="page-head__title"><?= e($item['title']) ?></h2><p class="page-head__sub"><?= e($item['type']) ?> · <?= e($item['category']) ?> · <?= e($item['status']) ?></p></div><div class="page-head__actions"><a class="btn btn--outline" href="<?= url('/admin/lost-found/' . $item['public_id'] . '/edit') ?>"><?= App\Support\Icons::render('edit', 14) ?> Edit</a><?php if ($item['status'] !== 'Returned'): ?><form method="POST" action="<?= url('/admin/lost-found/' . $item['public_id'] . '/returned') ?>"><?= csrf_field() ?><button class="btn btn--primary" type="submit">Mark Returned</button></form><?php endif; ?><?php if ($item['status'] !== 'Archived'): ?><form method="POST" action="<?= url('/admin/lost-found/' . $item['public_id'] . '/archive') ?>"><?= csrf_field() ?><button class="btn btn--ghost" type="submit">Archive</button></form><?php endif; ?></div></div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Item Record</div></div><span class="badge <?= e(status_class($item['status'])) ?>"><?= e($item['status']) ?></span></div><p class="content-lede"><?= nl2br(e($item['description'] ?: 'No description provided.')) ?></p><div class="event-facts"><div><span>Type</span><strong><?= e($item['type']) ?></strong></div><div><span>Location</span><strong><?= e($item['location']) ?></strong></div><div><span>Date occurred</span><strong><?= e(fmt_datetime($item['date_occurred'])) ?></strong></div><div><span>Custody</span><strong><?= e($item['custody']) ?></strong></div><div><span>Reported by</span><strong><?= e($item['reported_by']) ?></strong></div><div><span>Contact</span><strong><?= e($item['contact']) ?></strong></div></div></section>
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Ownership Claims</div><p class="panel-card__sub">Review proof submitted by students.</p></div><span class="badge badge--brand"><?= count($claims) ?> claims</span></div>
            <?php if ($claims === []): ?><div class="empty-state empty-state--compact"><strong>No claims submitted yet.</strong></div><?php else: ?>
                <div class="claim-list">
                    <?php foreach ($claims as $claim): ?>
                        <article class="claim-card">
                            <div class="claim-card__head"><div><strong><?= e($claim['claimant_name']) ?></strong><span><?= e($claim['claimant_email']) ?> · <?= e($claim['student_id']) ?> · <?= e(fmt_datetime($claim['created_at'])) ?></span></div><span class="badge <?= e(status_class($claim['status'])) ?>"><?= e($claim['status']) ?></span></div>
                            <p><strong>Proof:</strong> <?= e($claim['proof']) ?></p>
                            <p><?= e($claim['message']) ?></p>
                            <?php if ($claim['status'] === 'Pending'): ?>
                                <div class="claim-card__actions"><form method="POST" action="<?= url('/admin/lost-found/' . $item['public_id'] . '/claims/' . $claim['public_id'] . '/approve') ?>"><?= csrf_field() ?><button class="btn btn--primary btn--sm" type="submit">Verify Claim</button></form><form method="POST" action="<?= url('/admin/lost-found/' . $item['public_id'] . '/claims/' . $claim['public_id'] . '/reject') ?>"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Reject</button></form></div>
                            <?php elseif ($claim['verified_by']): ?><div class="cell-sub">Reviewed by <?= e($claim['verified_by']) ?> on <?= e(fmt_datetime($claim['verified_at'])) ?></div><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <aside class="detail-grid__side"><section class="card panel-card"><div class="panel-title">Record Details</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono"><?= e($item['public_id']) ?></dd></div><div><dt>Category</dt><dd><?= e($item['category']) ?></dd></div><div><dt>Student ID</dt><dd><?= e($item['student_id'] ?: '—') ?></dd></div><div><dt>Attachment</dt><dd><?= e($item['attachment'] ?: 'None') ?></dd></div><div><dt>Posted</dt><dd><?= e(fmt_datetime($item['posted_at'])) ?></dd></div><div><dt>Updated</dt><dd><?= e(fmt_datetime($item['updated_at'])) ?></dd></div></dl></section></aside>
</div>
