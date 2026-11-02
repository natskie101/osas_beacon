<?php
/** @var string $pageTitle */
/** @var string $pageSubtitle */
/** @var array<string, mixed> $user */
/** @var string $role */
/** @var string $initials */
$bellHref = '/notifications';
$unreadNotifications = App\Models\Notification::unreadCount((int) ($user['id'] ?? 0));
?>
<header class="topbar">
    <button type="button" class="icon-btn menu-btn" id="menu-btn" aria-label="Show modules">
        <?= App\Support\Icons::render('menu', 18) ?>
    </button>
    <div class="topbar__heading">
        <h1 class="topbar__title"><?= e($pageTitle ?? 'OSAS Portal') ?></h1>
        <div class="topbar__sub"><?= e($pageSubtitle ?? '') ?></div>
    </div>
    <div class="topbar__actions">
        <a class="icon-btn topbar__bell" href="<?= url($bellHref) ?>" title="Notifications" aria-label="Notifications">
            <?= App\Support\Icons::render('bell', 18) ?>
            <?php if ($unreadNotifications > 0): ?><span class="topbar__bell-count"><?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?></span><?php endif; ?>
        </a>
        <details class="account">
            <summary class="topbar__avatar" aria-label="Account menu"><?= e($initials) ?></summary>
            <div class="account__panel">
                <div class="account__head">
                    <span class="avatar"><?= e($initials) ?></span>
                    <div>
                        <div class="account__name"><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></div>
                        <div class="account__role"><?= e($user['role'] ?? '') ?></div>
                    </div>
                </div>
                <div class="account__mail"><?= e($user['email'] ?? '') ?></div>
                <form method="POST" action="<?= url('/logout') ?>" class="account__form" data-confirm="Sign out of the portal?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--primary btn--block">
                        <?= App\Support\Icons::render('logout', 14) ?>
                        Sign out
                    </button>
                </form>
            </div>
        </details>
    </div>
</header>
