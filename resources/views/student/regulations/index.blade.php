@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $regulations */
/** @var list<string> $categories */
/** @var array<string, int> $categoryCounts */
/** @var int $totalPublished */
/** @var array<string, string> $filters */
$currentCategory = (string) ($filters['category'] ?? '');
$search = (string) ($filters['search'] ?? '');
$pillUrl = static function (?string $category) use ($search): string {
    $query = array_filter(['search' => $search, 'category' => (string) $category], static fn (string $value): bool => $value !== '');
    return url('/student/rules' . ($query === [] ? '' : '?' . http_build_query($query)));
};
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
</div>

<form class="filter-bar sr-search" method="GET" action="{!! url('/student/rules') !!}">
    <div class="field">
        <span class="input-group"><span class="input-group__ico">{!! App\Support\Icons::render('search', 15) !!}</span><input class="input" name="search" value="{{ $search }}" placeholder="Search by rule title or category..."></span>
    </div>
</form>

<div class="sr-pills">
    <a class="sr-pill{!! $currentCategory === '' ? ' sr-pill--on' : '' !!}" href="{!! $pillUrl(null) !!}">All Rules ({!! number_format($totalPublished) !!})</a>
    <?php foreach ($categories as $category): ?>
        <a class="sr-pill{!! $currentCategory === $category ? ' sr-pill--on' : '' !!}" href="{!! $pillUrl($category) !!}">{{ $category }} ({!! number_format($categoryCounts[$category] ?? 0) !!})</a>
    <?php endforeach; ?>
</div>

<?php if ($regulations === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No policies found.</strong><div>Try another search term or category.</div></div></div>
<?php else: ?>
    <div class="sr-list">
        <?php foreach ($regulations as $regulation): ?>
            <article class="sr-card">
                <div class="sr-card__head">
                    <span class="sr-code">{{ $regulation['reference_code'] }}</span>
                    <span class="sr-badge">In force</span>
                </div>
                <h3 class="sr-card__title">{{ $regulation['title'] }}</h3>
                <p class="sr-card__desc">{{ $regulation['summary'] ?: App\Support\Html::summary($regulation['body'], 220) }}</p>
                <div class="sr-card__facts">
                    <span>{!! App\Support\Icons::render('scale', 13) !!} {{ $regulation['category'] }}</span>
                    <span>{!! App\Support\Icons::render('calendar', 13) !!} Revised: {{ fmt_date($regulation['updated_at']) }}</span>
                </div>
                <a class="btn sr-view-btn" href="{!! url('/student/rules/' . $regulation['public_id']) !!}">View Policy</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

@endsection
