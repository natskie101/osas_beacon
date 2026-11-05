<?php
/** @var list<array<string, mixed>> $modules */
/** @var array<string, array<string, mixed>> $progress */
/** @var array<string, mixed> $summary */
<<<<<<< HEAD
/** @var array<string, string> $filters */
$contentLabel = static function (array $module): string {
    $type = (string) ($module['content_type'] ?? 'Video Presentation');
    $label = match ($type) {
        'File', 'Document / PDF', 'Slide Deck' => 'File',
        'Link', 'Video Presentation', 'Web URL / Embed' => 'Video',
        default => $type,
    };

    return $label . ' (' . (int) $module['duration_minutes'] . ' mins)' . ($module['verification_method'] === 'Quiz Assessment' ? ' + Quiz' : '');
};
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
</div>

<form class="filter-bar or-search" method="GET" action="<?= url('/student/orientation') ?>">
    <div class="field">
        <span class="input-group">
            <span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span>
            <input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search orientation modules...">
        </span>
    </div>
</form>

<div class="or-list">
    <?php foreach ($modules as $module): ?>
        <?php
        $itemProgress = $progress[(string) $module['id']] ?? null;
        $status = $itemProgress['status'] ?? 'Not Started';
        $learners = max(0, (int) $module['learners']);
        $completions = max(0, (int) $module['completions']);
        ?>
        <article class="or-module">
            <div class="or-module__head">
                <div class="or-module__title-wrap">
                    <span class="or-code"><?= e($module['public_id']) ?></span>
                    <h3><?= e($module['title']) ?></h3>
                </div>
                <span class="or-badge <?= $module['mandatory'] ? 'or-badge--mandatory' : 'or-badge--recommended' ?>"><?= $module['mandatory'] ? 'MANDATORY' : 'RECOMMENDED' ?></span>
            </div>
            <p class="or-module__desc"><?= e($module['description'] ?: App\Support\Html::summary($module['body'], 160)) ?></p>
            <div class="or-module__meta">
                <div class="or-module__progress"><span style="width: <?= min(100, (float) $module['completion_rate']) ?>%"></span></div>
                <div class="or-module__type"><?= e($contentLabel($module)) ?></div>
                <div class="or-module__score"><?= number_format((float) $module['completion_rate'], 1) ?>% (<?= number_format($completions) ?>/<?= number_format($learners) ?>)</div>
            </div>
            <div class="or-module__foot">
                <span><?= e(fmt_date($module['updated_at'])) ?> &middot; By <?= e($module['department'] ?: 'OSAS') ?></span>
                <?= App\Support\Icons::render('chevronRight', 14) ?>
            </div>
            <a class="or-module__link" href="<?= url('/student/orientation/' . $module['public_id']) ?>" aria-label="<?= e($module['title']) ?>"></a>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($modules === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No orientation modules match your search.</strong></div></div>
<?php endif; ?>
=======
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
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
