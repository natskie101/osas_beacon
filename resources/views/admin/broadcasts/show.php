<?php
/** @var array<string, mixed> $broadcast */
/** @var list<array<string, mixed>> $recipients */
?><div class="page-head">
    <div><div class="page-head__eyebrow"><a href="<?= url('/admin/broadcasts') ?>">Broadcasts</a> / <?= e($broadcast['public_id']) ?></div><h2 class="page-head__title"><?= e($broadcast['subject']) ?></h2><p class="page-head__sub"><?= e($broadcast['channel']) ?> · <?= e($broadcast['audience']) ?> · <?= e($broadcast['status']) ?></p></div>
    <div class="page-head__actions">
        <?php if ($broadcast['status'] !== 'Sent'): ?>
            <a class="btn btn--outline" href="<?= url('/admin/broadcasts/' . $broadcast['public_id'] . '/edit') ?>"><?= App\Support\Icons::render('edit', 14) ?> Edit</a>
            <form method="POST" action="<?= url('/admin/broadcasts/' . $broadcast['public_id'] . '/send') ?>"><?= csrf_field() ?><button class="btn btn--primary" type="submit"><?= App\Support\Icons::render('send', 14) ?> Send Now</button></form>
        <?php else: ?><span class="badge badge--published">Sent <?= e(time_ago($broadcast['sent_at'])) ?></span><?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card">
            <div class="panel-card__head"><div><div class="panel-title">Message</div></div><span class="badge <?= e(status_class($broadcast['status'])) ?>"><?= e($broadcast['status']) ?></span></div>
            <div class="broadcast-message"><?= nl2br(e($broadcast['message'])) ?></div>
        </section>
        <section class="card panel-card">
            <div class="panel-card__head"><div><div class="panel-title">Delivery</div><p class="panel-card__sub">Audience membership is resolved by the server at send time.</p></div></div>
            <div class="stats stats--compact">
                <div class="stat"><div class="stat__label">Recipients</div><div class="stat__value"><?= number_format((int) $broadcast['reach']) ?></div><div class="stat__sub">Matching active accounts</div></div>
                <div class="stat"><div class="stat__label">Delivered</div><div class="stat__value"><?= number_format((int) $broadcast['delivered']) ?></div><div class="stat__sub">Portal notifications</div></div>
                <div class="stat"><div class="stat__label">Opened</div><div class="stat__value"><?= number_format((int) $broadcast['opened']) ?></div><div class="stat__sub">Read in the portal</div></div>
            </div>
        </section>
        <section class="card panel-card">
            <div class="panel-card__head"><div><div class="panel-title">Recipient Preview</div><p class="panel-card__sub"><?= count($recipients) ?> active accounts currently match this audience.</p></div></div>
            <div class="mini-list">
                <?php foreach (array_slice($recipients, 0, 8) as $recipient): ?>
                    <div class="mini-item"><span class="avatar avatar--sm"><?= e(initials($recipient['first_name'] ?? '', $recipient['last_name'] ?? '')) ?></span><div><strong><?= e(trim(($recipient['first_name'] ?? '') . ' ' . ($recipient['last_name'] ?? ''))) ?></strong><span><?= e($recipient['email']) ?> · <?= e($recipient['role']) ?></span></div></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <aside class="detail-grid__side">
        <section class="card panel-card"><div class="panel-title">Broadcast Facts</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono"><?= e($broadcast['public_id']) ?></dd></div><div><dt>Channel</dt><dd><?= e($broadcast['channel']) ?></dd></div><div><dt>Audience</dt><dd><?= e($broadcast['audience']) ?></dd></div><div><dt>Status</dt><dd><?= e($broadcast['status']) ?></dd></div><div><dt>Scheduled</dt><dd><?= e(fmt_datetime($broadcast['scheduled_at'])) ?></dd></div><div><dt>Sent</dt><dd><?= e(fmt_datetime($broadcast['sent_at'])) ?></dd></div><div><dt>Created</dt><dd><?= e(fmt_datetime($broadcast['created_at'])) ?></dd></div></dl></section>
        <section class="card panel-card"><div class="panel-title">Delivery Notes</div><p class="panel-card__text">Portal notifications appear in every recipient's bell. Email copies are sent through <?= e((string) config('mail.mailer', 'log')) ?> mail and logged to <span class="mono">storage/logs/mail.log</span> during development.</p></section>
    </aside>
</div>
