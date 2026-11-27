@extends('layouts.app')
@section('content')
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
        <h2 class="page-head__title">{{ $pageTitle }}</h2>
        <p class="page-head__sub">{{ $pageSubtitle }}</p>
    </div>
</div>

<form class="filter-bar or-search" method="GET" action="{!! url('/student/orientation') !!}">
    <div class="field">
        <span class="input-group">
            <span class="input-group__ico">{!! App\Support\Icons::render('search', 15) !!}</span>
            <input class="input" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search orientation modules...">
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
            <div class="or-module__body">
                <div class="or-module__head">
                    <span class="or-code">REF: {{ $module['public_id'] }}</span>
                    <span class="or-badge">{!! $status === 'Completed' ? 'Completed' : 'In Progress' !!}</span>
                </div>
                <h3 class="or-module__title"><a href="{!! url('/student/orientation/' . $module['public_id']) !!}">{{ $module['title'] }}</a></h3>
                <p class="or-module__desc">{{ $module['description'] ?: App\Support\Html::summary($module['body'], 180) }}</p>
                <div class="or-module__meta">
                    <span>{{ $contentLabel($module) }}</span>
                    <span>·</span>
                    <span>By {{ $module['department'] ?: 'Office of Student Affairs and Services' }}</span>
                    <span>·</span>
                    <span>Updated {{ fmt_date($module['updated_at']) }}</span>
                </div>
            </div>
            <a class="btn sr-view-btn" href="{!! url('/student/orientation/' . $module['public_id']) !!}">View Module</a>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($modules === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No orientation modules match your search.</strong></div></div>
<?php endif; ?>

@endsection
