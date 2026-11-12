<?php
/** @var list<array<string, mixed>> $navItems */
/** @var string $pageKey */
/** @var string $portalLabel */
/** @var array<string, mixed> $user */
/** @var string $initials */
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
        {!! App\Support\Icons::beacon(26, 2.2) !!}
        <div>
            <div class="sidebar__wordmark">BEACON</div>
            <div class="sidebar__tag">{{ $portalLabel }}</div>
        </div>
        <button type="button" class="sidebar__close" id="sidebar-close" aria-label="Close navigation">
            {!! App\Support\Icons::render('close', 16) !!}
        </button>
    </div>
    <nav class="nav" aria-label="OSAS portal navigation">
        <ul>
            <?php foreach ($navItems as $index => $item): ?>
                <li class="nav__item{!! ($pageKey ?? '') === ($item['page'] ?? '') ? ' nav__item--active' : '' !!}">
                    <a href="{{ url($item['path']) }}">
                        <span class="nav__num">{!! $index + 1 !!}.</span>
                        <span class="nav__ico">{!! App\Support\Icons::render($item['icon'], 15) !!}</span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar__foot">
        <div class="sidebar__user">
            <span class="avatar"><?php if ($profilePhotoFile !== null): ?><img class="avatar__photo" src="{!! url('/profile/photo') !!}" alt=""><?php else: ?>{{ $initials }}<?php endif; ?></span>
            <div>
                <div class="sidebar__user-name">{{ trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) }}</div>
                <div class="sidebar__user-role">{{ $user['role'] ?? '' }}</div>
            </div>
            <form method="POST" action="{!! url('/logout') !!}" class="sidebar__signout-form" data-confirm="Sign out of the portal?">
                {!! csrf_field() !!}
                <button type="submit" class="sidebar__signout">
                    {!! App\Support\Icons::render('logout', 15) !!}
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
