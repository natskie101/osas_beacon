<?php
/** @var array<string, mixed> $item */
/** @var array<string, mixed>|null $myClaim */
/** @var bool $ownReport */
/** @var list<array<string, mixed>> $claims */
/** @var array<string, mixed>|null $imageFile */
/** @var array<string, string> $errors */
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="<?= url('/student/lost-found') ?>">Lost and Found</a> / <?= e($item['public_id']) ?></div><h2 class="page-head__title"><?= e($item['title']) ?></h2><p class="page-head__sub"><?= e($item['type']) ?> · <?= e($item['category']) ?> · <?= e($item['status']) ?></p></div><span class="badge <?= e(status_class($item['status'])) ?>"><?= e($item['status']) ?></span></div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Item Information</div><p class="panel-card__sub">Posted <?= e(fmt_datetime($item['posted_at'])) ?></p></div></div><p class="content-lede"><?= nl2br(e($item['description'] ?: 'No description provided.')) ?></p><?php if ($imageFile !== null): ?><div class="lf-detail-media"><img src="<?= url('/student/lost-found/' . $item['public_id'] . '/image') ?>" alt="<?= e($item['title']) ?> photo" loading="lazy"></div><?php endif; ?><div class="event-facts"><div><span>Location</span><strong><?= e($item['location']) ?></strong></div><div><span>Date</span><strong><?= e(fmt_datetime($item['date_occurred'])) ?></strong></div><div><span>Custody</span><strong><?= e($item['custody']) ?></strong></div><div><span>Reported by</span><strong><?= e($item['reported_by']) ?></strong></div></div></section>
        <?php if ($ownReport && $claims !== []): ?>
            <section class="card panel-card"><div class="panel-title">Claims on Your Report</div><div class="claim-list"><?php foreach ($claims as $claim): ?><article class="claim-card"><div class="claim-card__head"><div><strong><?= e($claim['claimant_name']) ?></strong><span><?= e($claim['claimant_email']) ?> · <?= e(fmt_datetime($claim['created_at'])) ?></span></div><span class="badge <?= e(status_class($claim['status'])) ?>"><?= e($claim['status']) ?></span></div><p><?= e($claim['message']) ?></p></article><?php endforeach; ?></div></section>
        <?php elseif ($myClaim): ?>
            <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Your Claim</div><p class="panel-card__sub">Submitted <?= e(fmt_datetime($myClaim['created_at'])) ?></p></div><span class="badge <?= e(status_class($myClaim['status'])) ?>"><?= e($myClaim['status']) ?></span></div><p class="panel-card__text"><?= e($myClaim['message']) ?></p><div class="cell-sub">Proof: <?= e($myClaim['proof']) ?></div></section>
        <?php elseif (!$ownReport): ?>
            <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title"><?= $item['type'] === 'Found' ? 'Is this yours?' : 'Do you have information?' ?></div><p class="panel-card__sub">Submit proof or details for OSAS verification.</p></div><?= App\Support\Icons::render('shield', 22) ?></div>
                <?php if ($errors !== []): ?><div class="alert alert--danger"><div class="alert__text"><?= e(reset($errors)) ?></div></div><?php endif; ?>
                <?php if (in_array($item['status'], ['Open', 'Unclaimed', 'Claim Pending'], true)): ?>
                    <form method="POST" action="<?= url('/student/lost-found/' . $item['public_id'] . '/claim') ?>" class="form-grid">
                        <?= csrf_field() ?>
                        <div class="field"><label for="proof">Proof of ownership or information</label><textarea class="input" id="proof" name="proof" rows="3" required placeholder="Describe identifying details that only the owner would know."><?= e(old('proof')) ?></textarea></div>
                        <div class="field"><label for="message">Message to OSAS</label><textarea class="input" id="message" name="message" rows="3" required><?= e(old('message')) ?></textarea></div>
                        <button class="btn btn--primary" type="submit">Submit Claim</button>
                    </form>
                <?php else: ?><p class="panel-card__text">This item is no longer accepting claims.</p><?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
    <aside class="detail-grid__side"><section class="card panel-card"><div class="panel-title">Item Record</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono"><?= e($item['public_id']) ?></dd></div><div><dt>Type</dt><dd><?= e($item['type']) ?></dd></div><div><dt>Status</dt><dd><span class="badge <?= e(status_class($item['status'])) ?>"><?= e($item['status']) ?></span></dd></div><div><dt>Contact</dt><dd><?= e($item['contact']) ?></dd></div><div><dt>Student ID</dt><dd><?= e($item['student_id'] ?: '—') ?></dd></div></dl></section></aside>
</div>
