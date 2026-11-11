@extends('layouts.app')
@section('content')
<?php
/** @var list<array<string, mixed>> $infographics */
/** @var array<string, string> $errors */
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
?><div class="page-head">
    <div><h2 class="page-head__title">{{ $pageTitle }}</h2><p class="page-head__sub">{{ $pageSubtitle }}</p></div>
</div>

<?php if ($errors !== []): ?><div class="alert alert--danger"><span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text">{{ reset($errors) }}</div></div></div><?php endif; ?>

<section class="card form-card">
    <div class="panel-card__head"><div><div class="panel-title">Add a picture</div><p class="panel-card__sub">Uploaded pictures rotate on the student dashboard, one every 7 seconds.</p></div></div>
    <form method="POST" action="{!! url('/admin/infographics') !!}" enctype="multipart/form-data">
        {!! csrf_field() !!}
        <div class="field">
            <label for="image">Picture</label>
            <label class="upload-drop" for="image">
                <span class="upload-drop__icon">{!! App\Support\Icons::render('upload', 24) !!}</span>
                <span data-upload-prompt><strong>Click to upload a picture</strong> or drag and drop</span>
                <small data-upload-prompt>JPG or PNG &middot; up to 5 MB</small>
                <span class="upload-drop__filename" data-upload-filename aria-live="polite" hidden></span>
            </label>
            <input class="upload-drop__input" id="image" name="image" type="file" accept="image/jpeg,image/png">
            <?php if ($errorFor('image')): ?><div class="form-error">{{ $errorFor('image') }}</div><?php endif; ?>
        </div>
        <div class="form-actions"><button class="btn btn--primary" type="submit">Add to Dashboard</button></div>
    </form>
</section>

<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Current pictures</div><div class="cell-sub">Shown on the student dashboard in upload order</div></div><span class="badge badge--brand">{!! number_format(count($infographics)) !!} pictures</span></div>
    <?php if ($infographics === []): ?>
        <div class="empty-state"><strong>No pictures yet.</strong><p>The student dashboard shows the default poster until you add one.</p></div>
    <?php else: ?>
        <div class="ingo-grid">
            <?php foreach ($infographics as $i => $ingo): $file = $ingo['file'] ?? null; ?>
                <div class="ingo-card">
                    <a class="ingo-card__thumb" href="{!! url('/admin/infographics/' . (int) $ingo['id'] . '/image') !!}" target="_blank" rel="noopener">
                        <img src="{!! url('/admin/infographics/' . (int) $ingo['id'] . '/image') !!}" alt="Dashboard picture {{ $i + 1 }}" loading="lazy">
                    </a>
                    <div class="ingo-card__body">
                        <div class="cell-title">{{ $file['original_name'] ?? 'Picture' }}</div>
                        <div class="cell-sub">{{ format_bytes((int) ($file['size_bytes'] ?? 0)) }} &middot; Added {{ fmt_date((string) $ingo['created_at']) }}</div>
                        <div class="cell-actions">
                            <a class="btn btn--ghost btn--sm" href="{!! url('/admin/infographics/' . (int) $ingo['id'] . '/image') !!}" target="_blank" rel="noopener">{!! App\Support\Icons::render('eye', 13) !!} View</a>
                            <form method="POST" action="{!! url('/admin/infographics/' . (int) $ingo['id'] . '/delete') !!}">
                                {!! csrf_field() !!}
                                <button class="btn btn--outline btn--sm" type="submit" data-confirm="Remove this picture from the student dashboard?">{!! App\Support\Icons::render('trash', 13) !!} Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

@endsection
