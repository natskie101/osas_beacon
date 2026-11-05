<?php
/** @var array<string, mixed> $pagination */
/** @var string $baseUrl */
/** @var array<string, string> $query */
/** @var string $label */
$query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '');
unset($query['page']);

$pageUrl = static function (int $page) use ($baseUrl, $query): string {
    $params = array_merge($query, ['page' => $page]);

    return url($baseUrl) . '?' . http_build_query($params);
};

$current = (int) $pagination['page'];
$last = (int) $pagination['last_page'];
$start = max(1, $current - 2);
$end = min($last, $current + 2);
?><div class="table-foot">
    <div><?= e($label) ?></div>
    <div class="pager" aria-label="Pagination">
        <?php if ($current > 1): ?>
            <a class="pager__btn pager__nav" href="<?= e($pageUrl($current - 1)) ?>" aria-label="Previous page"><?= App\Support\Icons::render('chevronLeft', 12) ?></a>
        <?php else: ?>
            <span class="pager__btn pager__nav" aria-disabled="true"><?= App\Support\Icons::render('chevronLeft', 12) ?></span>
        <?php endif; ?>

        <?php if ($start > 1): ?>
            <a class="pager__btn<?= $current === 1 ? ' pager__btn--on' : '' ?>" href="<?= e($pageUrl(1)) ?>">1</a>
            <?php if ($start > 2): ?><span class="pager__gap">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($page = $start; $page <= $end; $page++): ?>
            <a class="pager__btn<?= $page === $current ? ' pager__btn--on' : '' ?>" href="<?= e($pageUrl($page)) ?>"<?= $page === $current ? ' aria-current="page"' : '' ?>><?= $page ?></a>
        <?php endfor; ?>

        <?php if ($end < $last): ?>
            <?php if ($end < $last - 1): ?><span class="pager__gap">…</span><?php endif; ?>
            <a class="pager__btn<?= $current === $last ? ' pager__btn--on' : '' ?>" href="<?= e($pageUrl($last)) ?>"><?= $last ?></a>
        <?php endif; ?>

        <?php if ($current < $last): ?>
            <a class="pager__btn pager__nav" href="<?= e($pageUrl($current + 1)) ?>" aria-label="Next page"><?= App\Support\Icons::render('chevronRight', 12) ?></a>
        <?php else: ?>
            <span class="pager__btn pager__nav" aria-disabled="true"><?= App\Support\Icons::render('chevronRight', 12) ?></span>
        <?php endif; ?>
    </div>
</div>
