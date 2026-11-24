<?php
/** @var string $pageTitle */
/** @var string $pageSubtitle */
/** @var array<string, mixed> $user */
/** @var string $role */
/** @var string $initials */
$bellHref = '/notifications';
$userId = (int) ($user['id'] ?? 0);
$unreadNotifications = App\Models\Notification::unreadCount($userId);
$recentNotifications = App\Models\Notification::forUser($userId, 5);
?>
<header class="topbar">
    <button type="button" class="icon-btn menu-btn" id="menu-btn" aria-label="Show modules">
        {!! App\Support\Icons::render('menu', 18) !!}
    </button>
    <div class="topbar__heading">
        <h1 class="topbar__title">{{ $pageTitle ?? 'OSAS Portal' }}</h1>
        <div class="topbar__sub">{{ $pageSubtitle ?? '' }}</div>
    </div>
    <div class="topbar__actions">
        <button type="button" class="topbar__search-trigger" id="cmd-palette-trigger" aria-label="Quick jump (Ctrl+K)">
            {!! App\Support\Icons::render('search', 14) !!}
            <span>Jump to...</span>
            <kbd class="cmd-palette__kbd">Ctrl K</kbd>
        </button>
        <details class="notif-menu">
            <summary class="icon-btn topbar__bell" title="Notifications" aria-label="Notifications">
                {!! App\Support\Icons::render('bell', 18) !!}
                <?php if ($unreadNotifications > 0): ?><span class="topbar__bell-count">{!! $unreadNotifications > 99 ? '99+' : $unreadNotifications !!}</span><?php endif; ?>
            </summary>
            <div class="notif-menu__panel">
                <div class="notif-menu__head">
                    <span class="notif-menu__title">Notifications</span>
                    <?php if ($unreadNotifications > 0): ?>
                        <form method="POST" action="{!! url('/notifications/read-all') !!}">
                            {!! csrf_field() !!}
                            <button type="submit" class="notif-menu__mark-all">Mark all as read</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="notif-menu__list">
                    <?php if ($recentNotifications === []): ?>
                        <div class="notif-menu__empty">No notifications yet.</div>
                    <?php else: ?>
                        <?php foreach ($recentNotifications as $notif): ?>
                            <a class="notif-menu__item{!! empty($notif['read_at']) ? ' notif-menu__item--unread' : '' !!}" href="{!! url('/notifications') !!}">
                                <span class="notif-menu__item-icon">{!! App\Support\Icons::render('bell', 13) !!}</span>
                                <div class="notif-menu__item-body">
                                    <div class="notif-menu__item-title">{{ $notif['title'] }}</div>
                                    <div class="notif-menu__item-text">{{ $notif['message'] }}</div>
                                    <div class="notif-menu__item-time">{{ time_ago($notif['created_at']) }}</div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="notif-menu__foot">
                    <a href="{!! url('/notifications') !!}">View all in Inbox &rarr;</a>
                </div>
            </div>
        </details>
        <details class="account">
            <summary class="topbar__avatar" aria-label="Account menu"><?php if ($profilePhotoFile !== null): ?><img class="avatar__photo" src="{!! url('/profile/photo') !!}" alt=""><?php else: ?>{{ $initials }}<?php endif; ?></summary>
            <div class="account__panel">
                <div class="account__head">
                    <span class="avatar"><?php if ($profilePhotoFile !== null): ?><img class="avatar__photo" src="{!! url('/profile/photo') !!}" alt=""><?php else: ?>{{ $initials }}<?php endif; ?></span>
                    <div>
                        <div class="account__name">{{ trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) }}</div>
                        <div class="account__role">{{ $user['role'] ?? '' }}</div>
                    </div>
                </div>
                <div class="account__mail">{{ $user['email'] ?? '' }}</div>
                <?php if (in_array($role, ['Coordinator', 'Student', 'Administrator'], true)): ?>
                    <a class="account__profile-link" href="{!! url('/profile') !!}">My Profile</a>
                <?php endif; ?>
                <form method="POST" action="{!! url('/logout') !!}" class="account__form" data-confirm="Sign out of the portal?">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn--primary btn--block">
                        {!! App\Support\Icons::render('logout', 14) !!}
                        Sign out
                    </button>
                </form>
            </div>
        </details>
    </div>
</header>
