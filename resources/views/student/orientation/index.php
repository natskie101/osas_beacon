<?php
/** @var list<array<string, mixed>> $modules */
/** @var array<string, array<string, mixed>> $progress */
/** @var array<string, mixed> $summary */
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--outline" href="<?= url('/student/dashboard') ?>">Back to Dashboard</a></div>
<section class="card panel-card orientation-summary">
    <div class="panel-card__head"><div><div class="panel-title">Your Orientation Progress</div><p class="panel-card__sub"><?= (int) $summary['completed'] ?> of <?= (int) $summary['total'] ?> modules completed</p></div><span class="badge badge--brand"><?= number_format((float) $summary['percent'], 1) ?>%</span></div>
    <div class="progress-bar progress-bar--lg"><span style="width: <?= min(100, (float) $summary['percent']) ?>%"></span></div>
    <div class="orientation-summary__facts"><div><strong><?= (int) $summary['completed'] ?></strong><span>Completed</span></div><div><strong><?= (int) $summary['in_progress'] ?></strong><span>In progress</span></div><div><strong><?= (int) $summary['not_started'] ?></strong><span>Not started</span></div></div>
</section>
<div class="module-grid">
    <?php foreach ($modules as $module): ?>
        <?php $itemProgress = $progress[(string) $module['id']] ?? null; $status = $itemProgress['status'] ?? 'Not Started'; $percent = (int) ($itemProgress['progress'] ?? 0); ?>
        <article class="card module-card<?= $status === 'Completed' ? ' module-card--done' : '' ?>">
            <div class="module-card__head"><span class="module-card__ico"><?= App\Support\Icons::render('graduation', 18) ?></span><span class="badge <?= e(status_class($status)) ?>"><?= e($status) ?></span></div>
            <div class="module-card__category"><?= e($module['category']) ?> · <?= e($module['version']) ?></div>
            <h3 class="module-card__title"><a href="<?= url('/student/orientation/' . $module['public_id']) ?>"><?= e($module['title']) ?></a></h3>
            <p class="module-card__text"><?= e($module['description'] ?: App\Support\Html::summary($module['body'], 150)) ?></p>
            <div class="module-card__meta"><span><?= (int) $module['duration_minutes'] ?> min</span><span><?= $module['mandatory'] ? 'Mandatory' : 'Optional' ?></span><span><?= number_format((float) $module['completion_rate'], 1) ?>% completion</span></div>
            <div class="progress-bar"><span style="width: <?= min(100, $percent) ?>%"></span></div>
            <a class="btn btn--outline btn--block" href="<?= url('/student/orientation/' . $module['public_id']) ?>"><?= $status === 'Completed' ? 'Review Module' : 'Continue Module' ?></a>
        </article>
    <?php endforeach; ?>
</div>
<?php if ($modules === []): ?><div class="card panel-card"><div class="empty-state"><strong>No orientation modules are published yet.</strong></div></div><?php endif; ?>
