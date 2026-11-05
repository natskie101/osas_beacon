<?php
/** @var array<string, mixed> $ticket */
/** @var list<array<string, mixed>> $messages */
/** @var array<string, string> $errors */
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/student/support') ?>">Feedback and Report</a> / <?= e($ticket['public_id']) ?></div>
        <h2 class="page-head__title"><?= e($ticket['subject']) ?></h2>
        <p class="page-head__sub"><?= e($ticket['category']) ?> · <?= e($ticket['priority']) ?> priority · <?= e($ticket['status']) ?></p>
    </div>
    <span class="badge <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span>
</div>

<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card">
            <div class="panel-card__head">
                <div><div class="panel-title">Conversation</div><p class="panel-card__sub">All replies from OSAS appear here.</p></div>
                <span class="badge <?= e(status_class($ticket['priority'])) ?>"><?= e($ticket['priority']) ?></span>
            </div>
            <div class="message-thread">
                <?php foreach ($messages as $message): ?>
                    <article class="message <?= $message['role'] !== 'Student' ? 'message--staff' : '' ?>">
                        <div class="message__head"><strong><?= e($message['author_name']) ?></strong><span><?= e($message['role']) ?> · <?= e(fmt_datetime($message['created_at'])) ?></span></div>
                        <p><?= nl2br(e($message['body'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($ticket['status'] !== 'Closed'): ?>
            <section class="card panel-card">
                <div class="panel-card__head"><div><div class="panel-title">Add a Reply</div><p class="panel-card__sub">Provide more details or answer OSAS.</p></div><?= App\Support\Icons::render('send', 20) ?></div>
                <?php if ($errors !== []): ?><div class="alert alert--danger"><div class="alert__text"><?= e(reset($errors)) ?></div></div><?php endif; ?>
                <form method="POST" action="<?= url('/student/support/' . $ticket['public_id'] . '/reply') ?>" class="form-grid">
                    <?= csrf_field() ?>
                    <div class="field"><label for="body">Message</label><textarea class="input" id="body" name="body" rows="4" required></textarea></div>
                    <button class="btn btn--primary" type="submit">Send Reply</button>
                </form>
            </section>
        <?php endif; ?>
    </div>

    <aside class="detail-grid__side">
        <section class="card panel-card">
            <div class="panel-title">Ticket Details</div>
            <dl class="detail-list">
                <div><dt>Reference</dt><dd class="mono"><?= e($ticket['public_id']) ?></dd></div>
                <div><dt>Category</dt><dd><?= e($ticket['category']) ?></dd></div>
                <div><dt>Assigned to</dt><dd><?= e($ticket['assigned_to'] ?: 'OSAS Support') ?></dd></div>
                <div><dt>Submitted</dt><dd><?= e(fmt_datetime($ticket['submitted_at'])) ?></dd></div>
                <div><dt>Rating</dt><dd><?= $ticket['rating'] ? (int) $ticket['rating'] . '/5' : 'Not rated' ?></dd></div>
            </dl>
        </section>

        <?php if (in_array($ticket['status'], ['Resolved', 'Closed'], true) && !$ticket['rating']): ?>
            <section class="card panel-card">
                <div class="panel-title">Rate This Support</div>
                <form method="POST" action="<?= url('/student/support/' . $ticket['public_id'] . '/rate') ?>" class="form-grid">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="rating">Satisfaction</label>
                        <select class="input" id="rating" name="rating">
                            <option value="5">5 — Excellent</option>
                            <option value="4">4 — Very good</option>
                            <option value="3">3 — Satisfactory</option>
                            <option value="2">2 — Needs improvement</option>
                            <option value="1">1 — Poor</option>
                        </select>
                    </div>
                    <button class="btn btn--outline" type="submit">Submit Rating</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($ticket['status'] !== 'Closed'): ?>
            <section class="card panel-card">
                <div class="panel-title">Close Ticket</div>
                <p class="panel-card__text">Close this ticket once your concern has been fully answered.</p>
                <form method="POST" action="<?= url('/student/support/' . $ticket['public_id'] . '/close') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--ghost btn--block" type="submit">Close Ticket</button>
                </form>
            </section>
        <?php endif; ?>
    </aside>
</div>
