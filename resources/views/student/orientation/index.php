<?php
/** @var list<array<string, mixed>> $modules */
/** @var array<string, array<string, mixed>> $progress */
/** @var array<string, mixed> $summary */
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
                <span class="or-badge <?= $module['mandatory'] ? 'or-badge--mandatory' : 'or-badge--recommended' ?>"><?= $module['mandatory'] ? 'Mandatory' : 'Recommended' ?></span>
            </div>
            <p class="or-module__desc"><?= e($module['description'] ?: App\Support\Html::summary($module['body'], 160)) ?></p>
            <div class="or-module__meta">
                <span class="or-module__progress-status<?= $status === 'Completed' ? ' or-module__progress-status--completed' : ' or-module__progress-status--in-progress' ?>"><?= $status === 'Completed' ? 'COMPLETED' : 'IN PROGRESS' ?></span>
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
