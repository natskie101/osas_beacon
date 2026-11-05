<?php
/** @var array<string, mixed> $regulation */
/** @var list<string> $sanctions */
<<<<<<< HEAD
?>
<div class="sr-detail">
    <section class="sr-detail__hero">
        <div class="sr-detail__hero-category"><?= e(mb_strtoupper((string) $regulation['category'])) ?></div>
        <h1 class="sr-detail__hero-title"><?= e($regulation['title']) ?></h1>
        <div class="sr-detail__hero-sub"><?= e($regulation['approval_body'] ?: 'Office of Student Affairs and Services') ?></div>
    </section>

    <div class="sr-detail__ref">
        <span class="sr-code"><?= e($regulation['reference_code']) ?></span>
        <span class="sr-detail__ref-note">Official student policy</span>
        <span class="sr-badge">IN FORCE</span>
    </div>

    <h2 class="sr-detail__title"><?= e($regulation['title']) ?></h2>

    <div class="sr-facts">
        <div><span><?= App\Support\Icons::render('scale', 11) ?> Category</span><strong><?= e($regulation['category']) ?></strong></div>
        <div><span><?= App\Support\Icons::render('calendar', 11) ?> Last Revised</span><strong><?= e(fmt_date($regulation['updated_at'])) ?><?= $regulation['approval_body'] ? '<br><small>By: ' . e($regulation['approval_body']) . '</small>' : '' ?></strong></div>
        <div><span><?= App\Support\Icons::render('clock', 11) ?> Effective Date</span><strong><?= e($regulation['effective_date'] ?: 'Not set') ?></strong></div>
    </div>

    <section class="card panel-card sr-policy-card">
        <article class="sr-detail__body">
            <?= body_html($regulation['body']) ?>
        </article>
    </section>

    <?php if ($sanctions !== []): ?>
        <section class="sr-sanctions">
            <?php foreach ($sanctions as $index => $sanction): ?>
                <?php
                $tone = match ($index) { 0 => 'red', 1 => 'amber', default => 'gray' };
                $tier = is_array($sanction) ? (string) ($sanction['tier'] ?? '') : '';
                $text = is_array($sanction) ? (string) ($sanction['text'] ?? '') : (string) $sanction;
                $fallback = $index === 0 ? 'First Offense' : ($index === 1 ? 'Second Offense' : ($index === 2 ? 'Third Offense' : 'Offense ' . ($index + 1)));
                ?>
                <div class="sr-sanction sr-sanction--<?= $tone ?>">
                    <strong><?= e($tier !== '' ? $tier : $fallback) ?>:</strong>
                    <span><?= e($text) ?></span>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <form method="POST" action="<?= url('/student/rules/' . $regulation['public_id'] . '/acknowledge') ?>" class="sr-ack">
        <?= csrf_field() ?>
        <button class="btn sr-ack__btn" type="submit">I ACKNOWLEDGE THIS POLICY</button>
        <div class="sr-ack__note">Acknowledgement is recorded for this session.</div>
    </form>
=======
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="<?= url('/student/rules') ?>">Rules and Regulations</a> / <?= e($regulation['reference_code']) ?></div><h2 class="page-head__title"><?= e($regulation['title']) ?></h2><p class="page-head__sub"><?= e($regulation['category']) ?> · Effective <?= e($regulation['effective_date']) ?></p></div></div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <article class="card panel-card content-article">
            <?php if (!empty($regulation['summary'])): ?><p class="content-lede"><?= e($regulation['summary']) ?></p><?php endif; ?>
            <div class="content-detail"><?= body_html($regulation['body']) ?></div>
            <?php if ($sanctions !== []): ?><div class="policy-sanctions"><div class="section-title">Sanctions and Penalties</div><ul><?php foreach ($sanctions as $sanction): ?><li><?= e($sanction) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        </article>
    </div>
    <aside class="detail-grid__side">
        <section class="card panel-card"><div class="panel-title">Policy Information</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono"><?= e($regulation['reference_code']) ?></dd></div><div><dt>Category</dt><dd><?= e($regulation['category']) ?></dd></div><div><dt>Effective</dt><dd><?= e($regulation['effective_date']) ?></dd></div><div><dt>Review</dt><dd><?= e($regulation['review_schedule']) ?></dd></div><div><dt>Approved by</dt><dd><?= e($regulation['approval_body']) ?></dd></div><div><dt>Updated</dt><dd><?= e(fmt_datetime($regulation['updated_at'])) ?></dd></div></dl></section>
        <section class="card panel-card"><div class="panel-title">Need clarification?</div><p class="panel-card__text">Contact the Office of Student Affairs and Services through the support desk if you need help understanding this policy.</p><a class="btn btn--outline btn--block" href="<?= url('/student/support') ?>">Open Support Desk</a></section>
    </aside>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
</div>
