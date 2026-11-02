<?php
/** @var array<string, mixed> $module */
/** @var list<string> $objectives */
/** @var array<string, mixed>|null $progress */
$status = $progress['status'] ?? 'Not Started';
$percent = (int) ($progress['progress'] ?? 0);
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="<?= url('/student/orientation') ?>">Orientation</a> / <?= e($module['category']) ?></div><h2 class="page-head__title"><?= e($module['title']) ?></h2><p class="page-head__sub"><?= (int) $module['duration_minutes'] ?> minutes · <?= e($module['version']) ?> · <?= $module['mandatory'] ? 'Mandatory' : 'Optional' ?></p></div><span class="badge <?= e(status_class($status)) ?>"><?= e($status) ?></span></div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <article class="card panel-card content-article">
            <?php if (!empty($module['description'])): ?><p class="content-lede"><?= e($module['description']) ?></p><?php endif; ?>
            <?php if ($objectives !== []): ?><div class="objective-list"><div class="section-title">Learning Objectives</div><ul><?php foreach ($objectives as $objective): ?><li><?= App\Support\Icons::render('check', 13) ?> <?= e($objective) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="content-detail"><?= body_html($module['body']) ?></div>
        </article>
        <section class="card panel-card">
            <div class="panel-card__head"><div><div class="panel-title"><?= $status === 'Completed' ? 'Module Completed' : 'Ready to finish this module?' ?></div><p class="panel-card__sub"><?= $status === 'Completed' ? 'Your completion has been recorded on your orientation record.' : 'Mark this module complete once you have reviewed the material.' ?></p></div><?= App\Support\Icons::render($status === 'Completed' ? 'checkCircle' : 'target', 22) ?></div>
            <?php if ($status !== 'Completed'): ?>
                <form method="POST" action="<?= url('/student/orientation/' . $module['public_id'] . '/complete') ?>"><?= csrf_field() ?><button class="btn btn--primary" type="submit"><?= App\Support\Icons::render('check', 14) ?> Mark Module Complete</button></form>
            <?php else: ?>
                <a class="btn btn--outline" href="<?= url('/student/orientation') ?>">Back to All Modules</a>
            <?php endif; ?>
        </section>
    </div>
    <aside class="detail-grid__side">
        <section class="card panel-card"><div class="panel-title">Your Progress</div><div class="progress-bar progress-bar--lg"><span style="width: <?= min(100, $percent) ?>%"></span></div><div class="cell-sub" style="margin-top:8px"><?= $percent ?>% complete</div><dl class="detail-list"><div><dt>Status</dt><dd><span class="badge <?= e(status_class($status)) ?>"><?= e($status) ?></span></dd></div><div><dt>Category</dt><dd><?= e($module['category']) ?></dd></div><div><dt>Department</dt><dd><?= e($module['department']) ?></dd></div><div><dt>Audience</dt><dd><?= e($module['audience']) ?></dd></div><div><dt>Last viewed</dt><dd><?= e(fmt_datetime($progress['last_viewed_at'] ?? null)) ?></dd></div></dl></section>
        <section class="card panel-card"><div class="panel-title">Need Help?</div><p class="panel-card__text">If a module will not load or you need an accessibility accommodation, contact the OSAS support desk.</p><a class="btn btn--outline btn--block" href="<?= url('/student/support') ?>">Contact OSAS</a></section>
    </aside>
</div>
