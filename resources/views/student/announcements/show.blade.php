@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $announcement */
/** @var list<array<string, mixed>> $attachments */
/** @var list<array<string, mixed>> $related */
?><div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="{!! url('/student/announcements') !!}">Announcements</a> / {{ $announcement['category'] }}</div>
        <h2 class="page-head__title" style="font-size: 28px;">{{ $announcement['title'] }}</h2>
        <p class="page-head__sub" style="font-size: 14px;">{{ $announcement['author'] }} / {{ fmt_datetime($announcement['published_at']) }} / {{ $announcement['audience'] }}</p>
    </div>
</div>

<article class="card panel-card content-article" style="max-width: 1200px;">
    <?php if (!empty($announcement['summary'])): ?><p class="content-lede" style="font-size: 16px;">{{ $announcement['summary'] }}</p><?php endif; ?>
    <div class="content-detail" style="font-size: 15px; line-height: 1.7;">{!! body_html($announcement['body']) !!}</div>

    <?php if ($attachments !== []): ?>
        <div class="content-attachments">
            <?php foreach ($attachments as $attachment): ?>
                <?php if (!empty($attachment['file_id'])): ?>
                    <a class="attachment-chip" href="{!! url('/student/announcements/' . $announcement['public_id'] . '/attachment/' . $attachment['file_id']) !!}">{!! App\Support\Icons::render('download', 14) !!}<span>{{ $attachment['name'] }}</span><em>Download{!! !empty($attachment['size']) ? ' / ' . e($attachment['size']) : '' !!}</em></a>
                <?php else: ?>
                    <span class="attachment-chip">{!! App\Support\Icons::render('fileText', 14) !!}<span>{{ $attachment['name'] }}</span><em>{{ $attachment['size'] ?? '' }}</em></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</article>

<?php if ($related !== []): ?>
    <section class="card panel-card" style="max-width: 1200px; margin-top: 20px;">
        <div class="panel-title">More Announcements</div>
        <div class="mini-list">
            <?php foreach (array_slice($related, 0, 5) as $item): ?>
                <a class="mini-item" href="{!! url('/student/announcements/' . $item['public_id']) !!}">
                    <span class="log-item__ico">{!! App\Support\Icons::render('megaphone', 13) !!}</span>
                    <div><strong>{{ $item['title'] }}</strong><span>{{ fmt_date($item['published_at']) }}</span></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

@endsection
