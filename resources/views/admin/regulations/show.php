<?php
/** @var array<string, mixed> $regulation */
<<<<<<< HEAD
/** @var list<array<string, mixed>|string> $sanctions */
?><div class="page-head">
    <div><div class="page-head__eyebrow"><a href="<?= url('/admin/regulations') ?>">Rules & Regulations</a> / <?= e($regulation['public_id']) ?></div><h2 class="page-head__title"><?= e($regulation['title']) ?></h2><p class="page-head__sub"><?= e($regulation['reference_code']) ?> · <?= e($regulation['category']) ?><?= !empty($regulation['severity']) ? ' · ' . e($regulation['severity']) : '' ?> · <?= e($regulation['status']) ?></p></div>
=======
/** @var list<string> $sanctions */
?><div class="page-head">
    <div><div class="page-head__eyebrow"><a href="<?= url('/admin/regulations') ?>">Rules & Regulations</a> / <?= e($regulation['public_id']) ?></div><h2 class="page-head__title"><?= e($regulation['title']) ?></h2><p class="page-head__sub"><?= e($regulation['reference_code']) ?> · <?= e($regulation['category']) ?> · <?= e($regulation['status']) ?></p></div>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    <div class="page-head__actions"><a class="btn btn--outline" href="<?= url('/admin/regulations/' . $regulation['public_id'] . '/edit') ?>"><?= App\Support\Icons::render('edit', 14) ?> Edit</a><?php if ($regulation['status'] !== 'Published'): ?><form method="POST" action="<?= url('/admin/regulations/' . $regulation['public_id'] . '/publish') ?>"><?= csrf_field() ?><button class="btn btn--primary" type="submit">Publish</button></form><?php endif; ?><?php if ($regulation['status'] !== 'Archived'): ?><form method="POST" action="<?= url('/admin/regulations/' . $regulation['public_id'] . '/archive') ?>"><?= csrf_field() ?><button class="btn btn--ghost" type="submit">Archive</button></form><?php endif; ?></div>
</div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card">
            <div class="panel-card__head"><div><div class="panel-title">Policy Content</div><p class="panel-card__sub">Student-facing policy text.</p></div><span class="badge <?= e(status_class($regulation['status'])) ?>"><?= e($regulation['status']) ?></span></div>
            <?php if (!empty($regulation['summary'])): ?><p class="content-lede"><?= e($regulation['summary']) ?></p><?php endif; ?>
            <div class="content-detail"><?= body_html($regulation['body']) ?></div>
<<<<<<< HEAD
            <?php if ($sanctions !== []): ?><div class="policy-sanctions"><div class="section-title">Sanctions and Penalties</div><ul><?php foreach ($sanctions as $sanction): ?><?php $tier = is_array($sanction) ? (string) ($sanction['tier'] ?? '') : ''; $text = is_array($sanction) ? (string) ($sanction['text'] ?? '') : (string) $sanction; ?><li><?= $tier !== '' ? '<strong>' . e($tier) . ':</strong> ' : '' ?><?= e($text) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
=======
            <?php if ($sanctions !== []): ?><div class="policy-sanctions"><div class="section-title">Sanctions and Penalties</div><ul><?php foreach ($sanctions as $sanction): ?><li><?= e($sanction) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        </section>
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Recent Activity</div></div><a class="link" href="<?= url('/admin/activity') ?>">View all</a></div><div class="mini-list"><?php foreach (array_slice($audit ?? [], 0, 6) as $entry): ?><div class="mini-item"><span class="log-item__ico"><?= App\Support\Icons::render($entry['icon'] ?? 'info', 13) ?></span><div><strong><?= e($entry['title']) ?></strong><span><?= e($entry['text']) ?></span></div><em><?= e(time_ago($entry['created_at'])) ?></em></div><?php endforeach; ?></div></section>
    </div>
    <aside class="detail-grid__side">
<<<<<<< HEAD
        <section class="card panel-card"><div class="panel-title">Governance</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono"><?= e($regulation['reference_code']) ?></dd></div><div><dt>Category</dt><dd><?= e($regulation['category']) ?></dd></div><div><dt>Severity</dt><dd><?= e($regulation['severity'] ?: 'Not rated') ?></dd></div><div><dt>Audience</dt><dd><?= e($regulation['audience']) ?></dd></div><div><dt>Effective</dt><dd><?= e($regulation['effective_date']) ?></dd></div><div><dt>Review</dt><dd><?= e($regulation['review_schedule']) ?></dd></div><div><dt>Approved by</dt><dd><?= e($regulation['approval_body']) ?></dd></div><div><dt>Views</dt><dd><?= number_format((int) $regulation['views']) ?></dd></div><div><dt>Updated</dt><dd><?= e(fmt_datetime($regulation['updated_at'])) ?></dd></div></dl></section>
=======
        <section class="card panel-card"><div class="panel-title">Governance</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono"><?= e($regulation['reference_code']) ?></dd></div><div><dt>Category</dt><dd><?= e($regulation['category']) ?></dd></div><div><dt>Audience</dt><dd><?= e($regulation['audience']) ?></dd></div><div><dt>Effective</dt><dd><?= e($regulation['effective_date']) ?></dd></div><div><dt>Review</dt><dd><?= e($regulation['review_schedule']) ?></dd></div><div><dt>Approved by</dt><dd><?= e($regulation['approval_body']) ?></dd></div><div><dt>Views</dt><dd><?= number_format((int) $regulation['views']) ?></dd></div><div><dt>Updated</dt><dd><?= e(fmt_datetime($regulation['updated_at'])) ?></dd></div></dl></section>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
    </aside>
</div>
