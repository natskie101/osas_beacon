<?php
/** @var array<string, mixed> $regulation */
/** @var list<string> $sanctions */
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
</div>
